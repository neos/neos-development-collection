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
            // "subject" is the to-be-moved node
            $subject = $this->getSubject();
            $succeedingSibling = $this->getSiblingNode();

            $precedingSibling = null;
            try {
                $precedingSibling = $this->findChildNodes($this->findParentNode($subject))->previous($succeedingSibling);
            } catch (\InvalidArgumentException $e) {
                // do nothing; $precedingSibling is null.
            }

            $hasEqualParentNode = $this->findParentNode($subject)->getNodeAggregateIdentifier()->equals($this->findParentNode($succeedingSibling)->getNodeAggregateIdentifier());

            // we render content directly as response of this operation, so we need to flush the caches
            $doFlushContentCache = $this->contentCacheFlusher->scheduleFlushNodeAggregate($subject->getContentStreamIdentifier(), $subject->getNodeAggregateIdentifier());
            $this->nodeAggregateCommandHandler->handleMoveNodeAggregate(
                new MoveNodeAggregate(
                    $subject->getContentStreamIdentifier(),
                    $subject->getDimensionSpacePoint(),
                    $subject->getNodeAggregateIdentifier(),
                    $hasEqualParentNode ? null : $this->findParentNode($succeedingSibling)->getNodeAggregateIdentifier(),
                    $precedingSibling ? $precedingSibling->getNodeAggregateIdentifier() : null,
                    $succeedingSibling->getNodeAggregateIdentifier(),
                    RelationDistributionStrategy::gatherAll(),
                    $this->getInitiatingUserIdentifier()
                )
            )->blockUntilProjectionsAreUpToDate();
            $doFlushContentCache();
            $parentOfSucceedingSibling = $this->findParentNode($succeedingSibling);
            if ($parentOfSucceedingSibling) {
                $this->contentCacheFlusher->flushNodeAggregate($parentOfSucceedingSibling->getContentStreamIdentifier(), $parentOfSucceedingSibling->getNodeAggregateIdentifier());

                $updateParentNodeInfo = new \Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateNodeInfo();
                $updateParentNodeInfo->setNode($parentOfSucceedingSibling);

                $this->feedbackCollection->add($updateParentNodeInfo);
            }

            $removeNode = new RemoveNode($subject, $this->findParentNode($this->getSiblingNode()));
            $this->feedbackCollection->add($removeNode);

            $this->finish($subject);
        }
    }
}
