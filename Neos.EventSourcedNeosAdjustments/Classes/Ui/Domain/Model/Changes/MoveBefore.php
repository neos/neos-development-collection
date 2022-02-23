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

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\RemoveNode;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\MoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\RelationDistributionStrategy;

class MoveBefore extends AbstractStructuralChange
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
        $siblingNode = $this->getSiblingNode();
        if (is_null($siblingNode)) {
            return false;
        }
        $parent = $this->findParentNode($siblingNode);
        $nodeType = $this->subject->getNodeType();

        return $parent && $this->isNodeTypeAllowedAsChildNode($parent, $nodeType);
    }

    public function getMode(): string
    {
        return 'before';
    }

    /**
     * Applies this change
     */
    public function apply(): void
    {
        $succeedingSibling = $this->getSiblingNode();
        // "subject" is the to-be-moved node
        $subject = $this->subject;
        $parentNode = $subject ? $this->findParentNode($subject) : null;
        $succeedingSiblingParent = $succeedingSibling ? $this->findParentNode($succeedingSibling) : null;
        if ($this->canApply() && !is_null($subject) && !is_null($succeedingSibling)
            && !is_null($parentNode) && !is_null($succeedingSiblingParent))
        {
            $precedingSibling = null;
            try {
                $precedingSibling = $this->findChildNodes($parentNode)
                    ->previous($succeedingSibling);
            } catch (\InvalidArgumentException $e) {
                // do nothing; $precedingSibling is null.
            }

            $hasEqualParentNode = $parentNode->getNodeAggregateIdentifier()
                ->equals($succeedingSiblingParent->getNodeAggregateIdentifier());

            // we render content directly as response of this operation, so we need to flush the caches
            $doFlushContentCache = $this->contentCacheFlusher->scheduleFlushNodeAggregate(
                $subject->getContentStreamIdentifier(),
                $subject->getNodeAggregateIdentifier()
            );
            $this->nodeAggregateCommandHandler->handleMoveNodeAggregate(
                new MoveNodeAggregate(
                    $subject->getContentStreamIdentifier(),
                    $subject->getDimensionSpacePoint(),
                    $subject->getNodeAggregateIdentifier(),
                    $hasEqualParentNode
                        ? null
                        : $succeedingSiblingParent->getNodeAggregateIdentifier(),
                    $precedingSibling?->getNodeAggregateIdentifier(),
                    $succeedingSibling->getNodeAggregateIdentifier(),
                    RelationDistributionStrategy::STRATEGY_GATHER_ALL,
                    $this->getInitiatingUserIdentifier()
                )
            )->blockUntilProjectionsAreUpToDate();
            $doFlushContentCache();
            $this->contentCacheFlusher->flushNodeAggregate(
                $succeedingSiblingParent->getContentStreamIdentifier(),
                $succeedingSiblingParent->getNodeAggregateIdentifier()
            );

            $updateParentNodeInfo
                = new \Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateNodeInfo();
            $updateParentNodeInfo->setNode($succeedingSiblingParent);

            $this->feedbackCollection->add($updateParentNodeInfo);

            $removeNode = new RemoveNode($subject, $succeedingSiblingParent);
            $this->feedbackCollection->add($removeNode);

            $this->finish($subject);
        }
    }
}
