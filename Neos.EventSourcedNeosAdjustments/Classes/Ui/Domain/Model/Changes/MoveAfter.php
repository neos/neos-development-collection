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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\RelationDistributionStrategy;
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
     *
     * @return boolean
     */
    public function canApply(): bool
    {
        $parent = $this->findParentNode($this->getSiblingNode());
        $nodeType = $this->getSubject()->getNodeType();

        return $this->isNodeTypeAllowedAsChildNode($parent, $nodeType);
    }

    public function getMode()
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
        if ($this->canApply()) {
            // "subject" is the to-be-moved node
            $subject = $this->getSubject();
            $precedingSibling = $this->getSiblingNode();
            $parentNodeOfPreviousSibling = $this->findParentNode($precedingSibling);

            $succeedingSibling = null;
            try {
                $succeedingSibling = $this->findChildNodes($parentNodeOfPreviousSibling)->next($precedingSibling);
            } catch (\InvalidArgumentException $e) {
                // do nothing; $succeedingSibling is null.
            }

            $hasEqualParentNode = $this->findParentNode($subject)->getNodeAggregateIdentifier()->equals($parentNodeOfPreviousSibling->getNodeAggregateIdentifier());

            $command = new MoveNodeAggregate(
                $subject->getContentStreamIdentifier(),
                $subject->getDimensionSpacePoint(),
                $subject->getNodeAggregateIdentifier(),
                $hasEqualParentNode ? null : $parentNodeOfPreviousSibling->getNodeAggregateIdentifier(),
                $precedingSibling ? $precedingSibling->getNodeAggregateIdentifier() : null,
                $succeedingSibling ? $succeedingSibling->getNodeAggregateIdentifier() : null,
                RelationDistributionStrategy::gatherAll(),
                $this->getInitiatingUserIdentifier()
            );

            // we render content directly as response of this operation, so we need to flush the caches
            $doFlushContentCache = $this->contentCacheFlusher->scheduleFlushNodeAggregate($subject->getContentStreamIdentifier(), $subject->getNodeAggregateIdentifier());
            $this->nodeAggregateCommandHandler->handleMoveNodeAggregate($command)
                ->blockUntilProjectionsAreUpToDate();
            $doFlushContentCache();
            if ($parentNodeOfPreviousSibling) {
                $this->contentCacheFlusher->flushNodeAggregate($parentNodeOfPreviousSibling->getContentStreamIdentifier(), $parentNodeOfPreviousSibling->getNodeAggregateIdentifier());

                $updateParentNodeInfo = new UpdateNodeInfo();
                $updateParentNodeInfo->setNode($parentNodeOfPreviousSibling);
                $this->feedbackCollection->add($updateParentNodeInfo);
            }

            $removeNode = new RemoveNode($subject, $this->findParentNode($this->getSiblingNode()));
            $this->feedbackCollection->add($removeNode);

            $this->finish($subject);
        }
    }
}
