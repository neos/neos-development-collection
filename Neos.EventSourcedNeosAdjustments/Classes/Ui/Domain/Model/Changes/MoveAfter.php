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
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;

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
        $parent = $this->getSiblingNode()->findParentNode();
        $nodeType = $this->getSubject()->getNodeType();

        return NodeInfoHelper::isNodeTypeAllowedAsChildNode($parent, $nodeType);
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
            $parentNodeOfPreviousSibling = $precedingSibling->findParentNode();

            $succeedingSibling = null;
            try {
                $succeedingSibling = $parentNodeOfPreviousSibling->findChildNodes()->next($precedingSibling);
            } catch (\InvalidArgumentException $e) {
                // do nothing; $succeedingSibling is null.
            }

            $hasEqualParentNode = $subject->findParentNode()->getNodeAggregateIdentifier()->equals($parentNodeOfPreviousSibling->getNodeAggregateIdentifier());

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

            $this->contentCacheFlusher->registerNodeChange($subject);
            $this->runtimeBlocker->blockUntilProjectionsAreUpToDate(
                $this->nodeAggregateCommandHandler->handleMoveNodeAggregate($command)
            );

            $updateParentNodeInfo = new UpdateNodeInfo();
            $updateParentNodeInfo->setNode($parentNodeOfPreviousSibling);

            $this->feedbackCollection->add($updateParentNodeInfo);

            $removeNode = new RemoveNode($subject, $this->getSiblingNode()->findParentNode());
            $this->feedbackCollection->add($removeNode);

            $this->finish($subject);
        }
    }
}
