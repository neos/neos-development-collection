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


    public function ensureIntegrityForPublishingOfNodes(array $nodesToPublish, Workspace $targetWorkspace): void
    {
        // TODO: !!!!!! DIMENSION SUPPORT?
        $nodesToPublish = NodePublishingIntegrityNodeListToPublish::createForNodes($nodesToPublish);

        // NodesToPublish is an array of nodes.
        // the context of the to-be-published nodes is the source workspace.
        foreach ($nodesToPublish as $node) {
            assert($node instanceof NodeInterface);
            // CHECK 1: TODO: an der Originalstelle darf nach dem Publish kein Kind mehr existieren (das würde sonst disconnected werden)
            // Originalstelle: wenn verschoben wurde


            //////////////////////////////////////////////////////////
            // CHECK 2: an der Zielstelle muss nach dem Publish der Parent existieren
            $contextProperties = $node->getContext()->getProperties();
            $contextProperties['workspaceName'] = $targetWorkspace->getName();
            $contextProperties['invisibleContentShown'] = true;
            //$contextProperties['removedContentShown'] = true; TODO TRUE ODER FALSE?
            $contextProperties['inaccessibleContentShown'] = true;
            $contextOfTargetWorkspace = $this->contextFactory->create($contextProperties);


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
