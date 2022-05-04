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

use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\RemoveNode;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\MoveNodeAggregate;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeMove\Command\RelationDistributionStrategy;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateNodeInfo;

class MoveAfter extends AbstractStructuralChange
{
    /**
     * @Flow\Inject
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * "Subject" is the to-be-moved node; the "sibling" node is the node after which the "Subject" should be copied.
     */
    public function canApply(): bool
    {
        if (is_null($this->subject)) {
            return false;
        }
        $sibling = $this->getSiblingNode();
        if (is_null($sibling)) {
            return false;
        }
        $parent = $this->findParentNode($sibling);
        $nodeType = $this->subject->getNodeType();

        return !is_null($parent) && $this->isNodeTypeAllowedAsChildNode($parent, $nodeType);
    }

    public function getMode(): string
    {
        return 'after';
    }

    /**
     * Applies this change
     *
     * @return void
     */
    public function apply(): void
    {
        $precedingSibling = $this->getSiblingNode();
        $parentNodeOfPreviousSibling = $precedingSibling ? $this->findParentNode($precedingSibling) : null;
        // "subject" is the to-be-moved node
        $subject = $this->subject;
        $parentNode = $this->subject ? $this->findParentNode($this->subject) : null;
        if ($this->canApply()
            && !is_null($subject)
            && !is_null($precedingSibling)
            && !is_null($parentNodeOfPreviousSibling)
            && !is_null($parentNode)
        ) {
            $succeedingSibling = null;
            try {
                $succeedingSibling = $this->findChildNodes($parentNodeOfPreviousSibling)->next($precedingSibling);
            } catch (\InvalidArgumentException $e) {
                // do nothing; $succeedingSibling is null.
            }

            $hasEqualParentNode = $parentNode->getNodeAggregateIdentifier()
                ->equals($parentNodeOfPreviousSibling->getNodeAggregateIdentifier());

            $command = new MoveNodeAggregate(
                $subject->getContentStreamIdentifier(),
                $subject->getDimensionSpacePoint(),
                $subject->getNodeAggregateIdentifier(),
                $hasEqualParentNode ? null : $parentNodeOfPreviousSibling->getNodeAggregateIdentifier(),
                $precedingSibling->getNodeAggregateIdentifier(),
                $succeedingSibling?->getNodeAggregateIdentifier(),
                RelationDistributionStrategy::STRATEGY_GATHER_ALL,
                $this->getInitiatingUserIdentifier()
            );

            // we render content directly as response of this operation, so we need to flush the caches
            $doFlushContentCache = $this->contentCacheFlusher->scheduleFlushNodeAggregate(
                $subject->getContentStreamIdentifier(),
                $subject->getNodeAggregateIdentifier()
            );
            $this->nodeAggregateCommandHandler->handleMoveNodeAggregate($command)
                ->blockUntilProjectionsAreUpToDate();
            $doFlushContentCache();
            $this->contentCacheFlusher->flushNodeAggregate(
                $parentNodeOfPreviousSibling->getContentStreamIdentifier(),
                $parentNodeOfPreviousSibling->getNodeAggregateIdentifier()
            );

            $updateParentNodeInfo = new UpdateNodeInfo();
            $updateParentNodeInfo->setNode($parentNodeOfPreviousSibling);
            $this->feedbackCollection->add($updateParentNodeInfo);

            $removeNode = new RemoveNode($subject, $parentNodeOfPreviousSibling);
            $this->feedbackCollection->add($removeNode);

            $this->finish($subject);
        }
    }
}
