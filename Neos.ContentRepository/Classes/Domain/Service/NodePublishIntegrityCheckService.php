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
 * It is called from various places in the upper layers of publishing, e.g.+
 * - {@see BackendServiceController::publishAction()} for publishing from the content module
 * - TODO for publishing from the workspace module
 *
 * Throws an error in case disconnected nodes might appear due to a publish.
 *
 * ## How can disconnected nodes occur?
 *
 * (TODO: Copy description from https://github.com/neos/neos-development-collection/issues/3383)
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


    // MovedTo handling:
    // when moving /foo/a to /bar/a, the following node records exist in the user WS:
    // Persistence_Object_Identifier | Identifier    |    Path    | isRemoved | movedTo |
    // 789                           | 12345         |    /foo/a  | true      | 91011   |   <-- the OLD NODE points to the NEW location
    // 91011                         | 12345         |    /bar/a  | false     | null    |   <-- this is the node we receive on publish from the UI.

    // the UI sends us the already-moved node and says "publish me" (the SECOND one in the table above).

    // In case of deletion, the UI sends us the REMOVED node and says "publish me"

    /**
     * @param array $nodesToPublish
     * @param Workspace $targetWorkspace this is the workspace we publish to; so normally the base workspace of the user's workspace.
     */
    public function ensureIntegrityForPublishingOfNodes(array $nodesToPublish, Workspace $targetWorkspace): void
    {
        // TODO: !!!!!! DIMENSION SUPPORT?
        print_r('!!!!!!!!!!! CALL FOR' . PHP_EOL);
        $nodesToPublish = NodePublishingIntegrityNodeListToPublish::createForNodes($nodesToPublish, $this->nodeDataRepository);
        foreach ($nodesToPublish as $node) {
            print_r("\t" . $node->getPath() . PHP_EOL);
        }

        // NodesToPublish is an array of nodes.
        // the context of the to-be-published nodes is the source workspace.
        foreach ($nodesToPublish as $node) {
            assert($node instanceof NodeInterface);
            print_r('###########################################' . PHP_EOL);
            print_r('START INTEGRITY CHECK FOR '. $node->getPath() . PHP_EOL);

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
                $this->assertThatNodeDoesNotHaveChildrenAfterPublish($originalPath, $contextOfTargetWorkspace, $nodesToPublish);
            } elseif ($node->isRemoved()) {
                // we have a DELETION (isRemoved=true), as we did not find $moveSourceShadowNodeData
                // => the deletion should not lead to any disconnected child nodes.
                $this->assertThatNodeDoesNotHaveChildrenAfterPublish($node->getPath(), $contextOfTargetWorkspace, $nodesToPublish);

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
                    throw new NodePublishingIntegrityCheckViolationException('Target parent gets moved away in same publish!'); // TODO: error liste
                }

                // parent already exists and it gets removed in the same publish => ERROR
                if ($nodesToPublish->isRemoved($node->getParentPath())) {
                    throw new NodePublishingIntegrityCheckViolationException('Target parent gets removed in same publish!'); // TODO: error liste
                }
            } else {
                // parent did not exist and will be created in the same publish => OK
                // existing == non-shadow, non-deleted
                if (!$nodesToPublish->isExistingNode($node->getParentPath())) {
                    // parent did not exists and it will NOT be created in the same publish
                    throw new NodePublishingIntegrityCheckViolationException('parent did not exists and it will NOT be created in the same publish'); // TODO: error liste
                }
            }
        }
    }

    private function assertThatNodeDoesNotHaveChildrenAfterPublish(string $originalPath, Context $contextOfTargetWorkspace, NodePublishingIntegrityNodeListToPublish $nodesToPublish)
    {
        // now, we find all children in $originalPath in the base workspace (e.g. live)
        $originalNodeInTargetWorkspace = $contextOfTargetWorkspace->getNode($originalPath);
        print_r("\t looking at original path " . $originalPath . PHP_EOL);
        if ($originalNodeInTargetWorkspace) {
            // original node DOES exist in the target workspace
            $childNodes = $originalNodeInTargetWorkspace->getChildNodes();

            // when a child node gets created in the publish => ERROR
            if ($nodesToPublish->containsExistingChildNodesOf($originalPath)) {
                throw new NodePublishingIntegrityCheckViolationException('Some children still exist after publish!');
            }
            if (count($childNodes) > 0) {
                // Before move child nodes existed at source location
                // those child nodes must be moved or deleted in the same publish
                // as soon as we find any child node not getting moved or removed => ERROR
                foreach ($childNodes as $childNode) {
                    print_r("\t\tchild => " . $childNode->getPath() . PHP_EOL);
                    assert($childNode instanceof NodeInterface);
                    $childIsMovedAway = $nodesToPublish->isMovedFrom($childNode->getPath());
                    $childIsRemoved = $nodesToPublish->isRemoved($childNode->getPath());

                    print_r("\t\t\t moved?" . ($childIsMovedAway ? 'yes' : 'no') . PHP_EOL);
                    print_r("\t\t\t removed?" . ($childIsRemoved ? 'yes' : 'no') . PHP_EOL);

                    $childGetsMovedOrRemovedInPublish = $childIsMovedAway || $childIsRemoved;
                    if (!$childGetsMovedOrRemovedInPublish) {
                        throw new NodePublishingIntegrityCheckViolationException('child node at path ' . $childNode->getPath() . ' still exists after publish');
                    }
                }
            }
        }
        // else:
        // original node does not exist anymore in the target workspace
        // f.e. because somebody else deleted it in the meantime.
        // => We do not need to do anything in this case, because then, also no children exist anymore.
    }
}
