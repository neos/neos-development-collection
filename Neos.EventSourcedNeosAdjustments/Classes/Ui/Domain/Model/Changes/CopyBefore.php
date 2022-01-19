<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Changes;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\CopyNodesRecursively;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

class CopyBefore extends AbstractStructuralChange
{

    /**
     * @Flow\Inject
     * @var NodeDuplicationCommandHandler
     */
    protected $nodeDuplicationCommandHandler;

    /**
     * "Subject" is the to-be-copied node; the "sibling" node is the node after which the "Subject" should be copied.
     *
     * @return boolean
     */
    public function canApply(): bool
    {
        $nodeType = $this->getSubject()->getNodeType();

        return $this->isNodeTypeAllowedAsChildNode($this->findParentNode($this->getSiblingNode()), $nodeType);
    }

    public function getMode()
    {
        return 'before';
    }

    /**
     * Applies this change
     *
     * @return void
     */
    public function apply(): void
    {
        if ($this->canApply()) {
            $subject = $this->getSubject();

            $succeedingSibling = $this->getSiblingNode();
            $parentNodeOfSucceedingSibling = $this->findParentNode($succeedingSibling);

            $command = CopyNodesRecursively::create(
                $this->contentGraph->getSubgraphByIdentifier(
                    $subject->getContentStreamIdentifier(),
                    $subject->getDimensionSpacePoint(),
                    $subject->getVisibilityConstraints()
                ),
                $subject,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($subject->getDimensionSpacePoint()),
                UserIdentifier::forSystemUser(), // TODO
                $parentNodeOfSucceedingSibling->getNodeAggregateIdentifier(),
                $succeedingSibling->getNodeAggregateIdentifier(),
                NodeName::fromString(uniqid('node-'))
            );

            $this->nodeDuplicationCommandHandler->handleCopyNodesRecursively($command)
                ->blockUntilProjectionsAreUpToDate();

            $newlyCreatedNode = $this->nodeAccessorFor($parentNodeOfSucceedingSibling)->findChildNodeConnectedThroughEdgeName($parentNodeOfSucceedingSibling, $command->getTargetNodeName());
            // we render content directly as response of this operation, so we need to flush the caches at the copy target
            $this->contentCacheFlusher->flushNodeAggregate($newlyCreatedNode->getContentStreamIdentifier(), $newlyCreatedNode->getNodeAggregateIdentifier());
            $this->finish($newlyCreatedNode);
            // NOTE: we need to run "finish" before "addNodeCreatedFeedback" to ensure the new node already exists when the last feedback is processed
            $this->addNodeCreatedFeedback($newlyCreatedNode);
        }
    }
}
