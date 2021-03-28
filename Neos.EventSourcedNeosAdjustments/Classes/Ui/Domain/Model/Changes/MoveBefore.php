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
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;

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
    public function canApply()
    {
        $parent = $this->getSiblingNode()->findParentNode();
        $nodeType = $this->getSubject()->getNodeType();

        return NodeInfoHelper::isNodeTypeAllowedAsChildNode($parent, $nodeType);
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
    public function apply()
    {
        if ($this->canApply()) {
            // "subject" is the to-be-moved node
            $subject = $this->getSubject();
            $succeedingSibling = $this->getSiblingNode();

            $precedingSibling = null;
            try {
                $precedingSibling = $subject->findParentNode()->findChildNodes()->previous($succeedingSibling);
            } catch (\InvalidArgumentException $e) {
                // do nothing; $precedingSibling is null.
            }

            $hasEqualParentNode = $subject->findParentNode()->getNodeAggregateIdentifier()->equals($succeedingSibling->findParentNode()->getNodeAggregateIdentifier());

            $command = new MoveNodeAggregate(
                $subject->getContentStreamIdentifier(),
                $subject->getDimensionSpacePoint(),
                $subject->getNodeAggregateIdentifier(),
                $hasEqualParentNode ? null : $succeedingSibling->findParentNode()->getNodeAggregateIdentifier(),
                $precedingSibling ? $precedingSibling->getNodeAggregateIdentifier() : null,
                $succeedingSibling->getNodeAggregateIdentifier(),
                RelationDistributionStrategy::gatherAll(),
                $this->getInitiatingUserIdentifier()
            );

            $this->contentCacheFlusher->registerNodeChange($subject);
            $this->runtimeBlocker->blockUntilProjectionsAreUpToDate(
                $this->nodeAggregateCommandHandler->handleMoveNodeAggregate($command)
            );

            $updateParentNodeInfo = new \Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateNodeInfo();
            $updateParentNodeInfo->setNode($succeedingSibling->findParentNode());

            $this->feedbackCollection->add($updateParentNodeInfo);

            $removeNode = new RemoveNode($subject, $this->getSiblingNode()->findParentNode());
            $this->feedbackCollection->add($removeNode);

            $this->finish($subject);
        }
    }
}
