<?php

namespace Neos\ContentRepository\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Dto\NodePublishingIntegrityNodeListToPublish;
use Neos\ContentRepository\Exception\NodePublishingIntegrityCheckViolationException;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Ui\Controller\BackendServiceController;

/**
 * This is an internal service to prevent disconnected nodes when publishing after move.
 *
 * It is called from various places in the upper layers of publishing, e.g.
 * - {@see BackendServiceController::publishAction()} for publishing from the content module
 *
 * Throws an error in case disconnected nodes would appear due to a publish.
 *
 * ## How can disconnected nodes occur?
 *
 * Let's say we have the following node structure in the beginning:
 *
 * |-- site
 * . |-- cr
 * . | |-- subpage
 * . |   |-- nested
 * . |-- other
 *
 * Now, user-demo moves /site/cr/subpage underneath /site/other/ in the user workspace. This means in the
 * user workspace the following status exists:
 * |-- site
 * . |-- cr
 * .   |-- subpage   SHADOW NODE in user-demo
 * .     |-- nested  SHADOW NODE in user-demo
 * . |-- other
 * .   |-- subpage   user-demo
 * .     |-- nested  user-demo
 *
 * Now, let's assume user-demo forgets about this (thus not publishing), and a few weeks later needs to do
 * a text change on subpage:
 * |-- site
 * . |-- cr
 * .   |-- subpage   live + SHADOW NODE in user-demo
 * .     |-- nested  live + SHADOW NODE in user-demo
 * . |-- other
 * .   |-- subpage   user-demo <-- THIS node gets edited by user-demo
 * .     |-- nested  user-demo
 *
 * Now user-demo publishes only  /sites/other/subpage which leads to the following structure:
 * |-- site
 * . |-- cr
 * .   |-- [NODE DOES NOT EXIST ANYMORE]
 * .     |-- nested  live + SHADOW NODE in user-demo   <-- !!BUG!!
 * . |-- other
 * .   |-- subpage
 * .     |-- nested  user-demo
 *
 * The first "nested" node (marked with !!BUG!!) is NOT visible anymore in live, because the parent does not exist
 * anymore. It's hard to detect this as user-demo, because user-demo sees the moved nested node.
 *
 * @Flow\Scope("singleton")
 */
class NodePublishIntegrityCheckService
{

    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $contentDimensionCombinator;

    /**
     * @Flow\Inject(lazy=false)
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject(lazy=false)
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;


    /**
     * @param NodeInterface[] $nodesToPublish
     * @param Workspace $targetWorkspace this is the workspace we publish to; so normally the base workspace of the user's workspace.
     * @throws NodePublishingIntegrityCheckViolationException
     */
    public function ensureIntegrityForPublishingOfNodes(array $nodesToPublish, Workspace $targetWorkspace): void
    {
        if (count($nodesToPublish) === 0) {
            return;
        }

        $integrityCheckResults = [];
        $changesGroupedByDimension = $this->groupChangesByEffectedDimensionAndPreset($nodesToPublish);
        foreach ($changesGroupedByDimension as $dimensionAndPreset => $nodesInDimensionToPublish) {
            $integrityCheckResults[$dimensionAndPreset] = $this->applyIntegrityCheckForChangeSet($nodesInDimensionToPublish, $targetWorkspace);
        }

        $this->throwExceptionWhenIntegrityGetsViolated($integrityCheckResults);
    }

    /**
     * This function checks which configured dimension presets are effected by the node changes.+
     * Given an EN dimension and a DE Dimension which acts as fallback for a CH Dimension. The following table
     * shows which dimensions are effected in which scenarios:
     *
     * +──────────────────────────────────────────────────────────+─────+─────+────────+
     * | Node changed:                                            | EN  | DE  | CH,DE  |
     * +──────────────────────────────────────────────────────────+─────+─────+────────+
     * | DE node, which is used as fallback for the CH dimension  |     | x   | x      |
     * | CH node, which shines through from DE dimension          |     | x   | x      |
     * | --------------                                           |     |     |        |
     * | DE node, where CH variant is materialized                |     | x   |        |
     * | Materialized CH variant                                  |     |     | x      |
     * | --------------                                           |     |     |        |
     * | CH only node                                             |     |     | x      |
     * | EN only node                                             | x   |     |        |
     * +──────────────────────────────────────────────────────────+─────+─────+────────+
     *
     *
     * @param NodeInterface[] $nodesToPublish
     * @return array array key is $dimensionName-$presets (language-en, language-ch,de), values is the list of effected nodes
     */
    protected function groupChangesByEffectedDimensionAndPreset(array $nodesToPublish): array
    {
        $changesByDimensionAndPresets = [];
        $sourceWorkspace = $nodesToPublish[0]->getWorkspace()->getName();

        // Changes in a single dimension can effect other dimensions and can result in disconnected nodes
        // For this reason, we iterate over every dimension and check, if any node in the nodes to publish
        // could effect the dimension.
        $contentDimensionsAndPresets = $this->contentDimensionPresetSource->getAllPresets();
        foreach ($contentDimensionsAndPresets as $dimension => $dimensionConfiguration) {
            foreach ($dimensionConfiguration['presets'] as $preset) {
                $contextProperties = [];
                $contextProperties['dimensions'][$dimension] = $preset['values'];
                $contextProperties['workspaceName'] = $sourceWorkspace;
                $contextProperties['removedContentShown'] = true;
                $context = $this->contextFactory->create($contextProperties);

                $filteredNodes = [];
                foreach ($nodesToPublish as $node) {
                    assert($node instanceof NodeInterface);
                    $nodeInPreset = $context->getNodeByIdentifier($node->getIdentifier());
                    if ($nodeInPreset && $nodeInPreset->getNodeData() === $node->getNodeData()) {
                        $filteredNodes[] = $node;
                    }
                }

                if (count($filteredNodes) === 0) {
                    continue;
                }

                $filteredNodesToPublish = NodePublishingIntegrityNodeListToPublish::createForNodes($filteredNodes, $this->nodeDataRepository);
                $changesByDimensionAndPresets[$dimension . '-' . implode(',', $preset['values'])] = $filteredNodesToPublish;
            }
        }

        return $changesByDimensionAndPresets;
    }

    /**
     * @param NodePublishingIntegrityNodeListToPublish $nodesToPublish
     * @param Workspace $targetWorkspace
     * @return string[] list of integrity violations
     */
    private function applyIntegrityCheckForChangeSet(NodePublishingIntegrityNodeListToPublish $nodesToPublish, Workspace $targetWorkspace): array
    {
        $integrityViolations = [];

        // NodesToPublish is an array of nodes.
        // the context of the to-be-published nodes is the source workspace.
        foreach ($nodesToPublish as $node) {
            assert($node instanceof NodeInterface);

            //////////////////////////////////////////////////////////
            // PREPARATION: Build $contextOfTargetWorkspace
            $contextProperties = $node->getContext()->getProperties();
            $contextProperties['workspaceName'] = $targetWorkspace->getName();
            // we want to show hidden nodes and nodes with access restrictions
            $contextProperties['invisibleContentShown'] = true;
            $contextProperties['inaccessibleContentShown'] = true;
            // we do not want to show removed nodes, as a removed node (= a shadow node) should be as "not existing"
            $contextProperties['removedContentShown'] = false;

            $contextOfTargetWorkspace = $this->contextFactory->create($contextProperties);

            //////////////////////////////////////////////////////////
            /// CHECK 1) On the source location of the node there must be no child nodes anymore.
            ///          child nodes on the source location would get disconnected on publish
            $moveSourceShadowNodeData = $this->nodeDataRepository->findOneByMovedTo($node->getNodeData());
            if ($moveSourceShadowNodeData) {
                // we have a MOVE.
                $originalPath = $moveSourceShadowNodeData->getPath();
                $integrityViolationsInNoChildrenCheck = $this->assertThatNodeDoesNotHaveChildrenAfterPublish($originalPath, $contextOfTargetWorkspace, $nodesToPublish);
                $integrityViolations = array_merge($integrityViolations, $integrityViolationsInNoChildrenCheck);
            } elseif ($node->isRemoved()) {
                // we have a DELETION (isRemoved=true), as we did not find $moveSourceShadowNodeData
                // => the deletion should not lead to any disconnected child nodes.
                $integrityViolationsInNoChildrenCheck = $this->assertThatNodeDoesNotHaveChildrenAfterPublish($node->getPath(), $contextOfTargetWorkspace, $nodesToPublish);
                $integrityViolations = array_merge($integrityViolations, $integrityViolationsInNoChildrenCheck);

                // in a simple removal, we do not need to check the future parent and therefore skip the second check
                continue;
            }


            //////////////////////////////////////////////////////////
            /// CHECK 2) On the target location a parent must exist after publish
            $parentNodeExistsInTargetWorkspace = $contextOfTargetWorkspace->getNode($node->getParentPath()) !== null;
            if ($parentNodeExistsInTargetWorkspace) {
                // parent already exists in target workspace and was not modified by publish => OK

                // parent already exists and it gets moved in the same publish => ERROR
                if ($nodesToPublish->isMovedFrom($node->getParentPath())) {
                    $integrityViolations[] = 'Target parent with path ' . $node->getParentPath() . ' gets moved away in same publish';
                }

                // parent already exists and it gets removed in the same publish => ERROR
                if ($nodesToPublish->isRemoved($node->getParentPath())) {
                    $integrityViolations[] = 'Target parent with path ' . $node->getParentPath() . ' gets removed in same publish!';
                }
            } else {
                // parent did not exist and will be created in the same publish => OK
                // existing == non-shadow, non-deleted
                if (!$nodesToPublish->isExistingNode($node->getParentPath())) {
                    // parent did not exists and it will NOT be created in the same publish
                    $integrityViolations[] = 'Parent with path ' . $node->getParentPath() . ' did not exists and it will NOT be created in the same publish';
                }
            }
        }
        return $integrityViolations;
    }

    /**
     * @param string $originalPath
     * @param Context $contextOfTargetWorkspace
     * @param NodePublishingIntegrityNodeListToPublish $nodesToPublish
     * @return string[] list of integrity violations
     */
    private function assertThatNodeDoesNotHaveChildrenAfterPublish(string $originalPath, Context $contextOfTargetWorkspace, NodePublishingIntegrityNodeListToPublish $nodesToPublish): array
    {
        $integrityViolations = [];

        // now, we find all children in $originalPath in the base workspace (e.g. live)
        $originalNodeInTargetWorkspace = $contextOfTargetWorkspace->getNode($originalPath);
        if ($originalNodeInTargetWorkspace) {
            // original node DOES exist in the target workspace
            $childNodes = $originalNodeInTargetWorkspace->getChildNodes();

            // when a child node gets created in the publish => ERROR
            if ($nodesToPublish->containsExistingChildNodesOf($originalPath)) {
                $integrityViolations[] = 'Some children of node with path ' . $originalPath . ' still exist after publish!';
            }
            if (count($childNodes) > 0) {
                // Before move child nodes existed at source location
                // those child nodes must be moved or deleted in the same publish
                // as soon as we find any child node not getting moved or removed => ERROR
                foreach ($childNodes as $childNode) {
                    assert($childNode instanceof NodeInterface);
                    $childIsMovedAway = $nodesToPublish->isMovedFrom($childNode->getPath());
                    $childIsRemoved = $nodesToPublish->isRemoved($childNode->getPath());

                    $childGetsMovedOrRemovedInPublish = $childIsMovedAway || $childIsRemoved;
                    if (!$childGetsMovedOrRemovedInPublish) {
                        $integrityViolations[] = 'child node at path ' . $childNode->getPath() . ' still exists after publish';
                    }
                }
            }
        }
        // else:
        // original node does not exist anymore in the target workspace
        // f.e. because somebody else deleted it in the meantime.
        // => We do not need to do anything in this case, because then, also no children exist anymore.
        return $integrityViolations;
    }

    /**
     * @param array $integrityViolations
     * @throws NodePublishingIntegrityCheckViolationException
     */
    protected function throwExceptionWhenIntegrityGetsViolated(array $integrityViolations)
    {
        $msg = "";
        foreach ($integrityViolations as $dimensionAndPreset => $violations) {
            foreach ($violations as $violation) {
                $msg .= $dimensionAndPreset . ': ' . $violation . PHP_EOL;
            }
        }

        if ($msg !== "") {
            throw new NodePublishingIntegrityCheckViolationException($msg);
        }
    }
}
