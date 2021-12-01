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

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\Dto\NodeMoveIntegrityCheckResult;
use Neos\ContentRepository\Domain\Service\Dto\NodeMoveIntegrityCheckResultPart;
use Neos\ContentRepository\Domain\Service\Dto\NodePublishingIntegrityNodeListToPublish;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\ContentRepository\Exception\NodeMoveIntegrityViolationException;
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
 * @internal
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
        $nodesToPublish = NodePublishingIntegrityNodeListToPublish::createForNodes($nodesToPublish);

        // NodesToPublish is an array of nodes.
        // the context of the to-be-published nodes is the source workspace.
        foreach ($nodesToPublish as $node) {
            assert($node instanceof NodeInterface);

            // PREPARATION: Build $contextOfTargetWorkspace
            $contextProperties = $node->getContext()->getProperties();
            $contextProperties['workspaceName'] = $targetWorkspace->getName();
            // we want to show hidden nodes and nodes with access restrictions
            $contextProperties['invisibleContentShown'] = true;
            $contextProperties['inaccessibleContentShown'] = true;
            // we do not want to show removed nodes, as a removed node (= a shadow node) should be as "not existing"
            $contextProperties['removedContentShown'] = false;

            $contextOfTargetWorkspace = $this->contextFactory->create($contextProperties);


            // CHECK 1: TODO: an der Originalstelle darf nach dem Publish kein Kind mehr existieren (das würde sonst disconnected werden)

            // 1 a) Originalstelle finden, wenn Node verschoben wurde.
            $moveSourceShadowNodeData = $this->nodeDataRepository->findOneByMovedTo($node->getNodeData());
            if ($moveSourceShadowNodeData) {
                // we have a MOVE.

                $originalPath = $moveSourceShadowNodeData->getPath();
                // b) now, we find all children in $originalPath in the base workspace (e.g. live)
                $originalNodeInTargetWorkspace = $contextOfTargetWorkspace->getNode($originalPath);
                if ($originalNodeInTargetWorkspace) {
                    // original node DOES exist in the target workspace
                    $childNodes = $originalNodeInTargetWorkspace->getChildNodes();

                    // -> wenn ein Kindknoten im publish erzeugt wirc, dann ist ein FEHLER.
                    if ($nodesToPublish->containsExistingChildNodesOf($originalPath)) {
                        throw new \RuntimeException('TODO');
                    }
                    if (count($childNodes) > 0) {
                        // Es gab an der Originalstelle vorher Kinder
                        // -> diese Kinder müssen entweder verschoben oder gelöscht werden im selben Publish.
                        // -> sobald wir EINEN Kindknoten finden, welcher NICHT verschoben wurde und nicht gelöscht wurde, ist das ein FEHLER.
                        foreach ($childNodes as $childNode) {
                            assert($childNode instanceof NodeInterface);
                            $childIsMovedAway = $nodesToPublish->isMovedFrom($childNode->getPath());
                            $childNodeIsRemoved = $nodesToPublish->isRemoved($childNode->getPath());

                            $childStillExists = !$childIsMovedAway && !$childNodeIsRemoved;
                            if ($childStillExists) {
                                throw new \RuntimeException('TODO');
                            }
                        }
                    }
                } else {
                    // original node does not exist anymore in the target workspace
                    // f.e. because somebody else deleted it in the meantime.
                    // => We do not need to do anything in this case, because then, also no children exist anymore.
                }

                // Laden aller Kinder, die gerade an der Originalstelle hängen im LIVE workspace.
            } else {
                // no MOVE, as we did not find $moveSourceShadowNodeData
                // => we do not need to do anything.
            }


            //////////////////////////////////////////////////////////
            // CHECK 2: an der Zielstelle muss nach dem Publish der Parent existieren


            $parentNodeExistsInTargetWorkspace = $contextOfTargetWorkspace->getNode($node->getParentPath()) !== null;
            if ($parentNodeExistsInTargetWorkspace) {
                // Parent gab es schon und er wurde nicht beeinträchtigt durch Publish (ggf. nur Properties geändert) -> OK
                // Parent gab es schon und er wird im selben Publish verschoben -> error
                // Parent gab es schon und er wird im selben Publish gelöscht -> error

                if ($nodesToPublish->isMovedFrom($node->getParentPath())) {
                    throw new \RuntimeException('TODO: ....'); // TODO: error liste
                }

                if ($nodesToPublish->isRemoved($node->getParentPath())) {
                    throw new \RuntimeException('TODO: ....'); // TODO: error liste
                }
            } else {
                // Parent gab es noch nicht und er wird im selben Publish angelegt -> OK
                // Parent gab es noch nicht und er wird NICHT im selben Publish angelegt -> error

                // existing == non-shadow, non-deleted
                if (!$nodesToPublish->isExistingNode($node->getParentPath())) {
                    throw new \RuntimeException('TODO: ....'); // TODO: error liste
                }
            }

        }


        // TODO: $checkResult = $this->checkIntegrityForDocumentNodeMove($nodeToMove, NodePaths::getParentPath($destinationPath));
        // TODO:if ($checkResult->hasIntegrityViolations()) {
        // TODO:   throw new NodeMoveIntegrityViolationException($checkResult->getPlainMessage(), 1635413769);
        // TODO:}
    }
}
