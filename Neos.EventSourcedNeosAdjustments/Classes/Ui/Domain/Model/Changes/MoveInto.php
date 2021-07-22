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

use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\RemoveNode;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateNodeInfo;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\MoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\RelationDistributionStrategy;
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;

class MoveInto extends AbstractStructuralChange
{
    /**
     * @Flow\Inject
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @var string
     */
    protected $parentContextPath;

    /**
     * @param string $parentContextPath
     */
    public function setParentContextPath($parentContextPath)
    {
        $this->parentContextPath = $parentContextPath;
    }

    /**
     * Get the sibling node
     *
     * @return NodeInterface
     */
    public function getParentNode(): ?NodeInterface
    {
        if ($this->parentContextPath === null) {
            return null;
        }

        return $this->nodeService->getNodeFromContextPath(
            $this->parentContextPath
        );
    }


    /**
     * Get the insertion mode (before|after|into) that is represented by this change
     *
     * @return string
     */
    public function getMode()
    {
        return 'into';
    }

    /**
     * Checks whether this change can be applied to the subject
     *
     * @return boolean
     */
    public function canApply(): bool
    {
        $parent = $this->getParentNode();
        $nodeType = $this->getSubject()->getNodeType();

        return NodeInfoHelper::isNodeTypeAllowedAsChildNode($parent, $nodeType);
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

            $nodeAccessor = $this->nodeAccessorManager->accessorFor($subject->getContentStreamIdentifier(), $subject->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
            $hasEqualParentNode = $nodeAccessor->findParentNode($subject)->getNodeAggregateIdentifier()->equals($this->getParentNode()->getNodeAggregateIdentifier());

            $this->contentCacheFlusher->registerNodeChange($subject);
            $this->nodeAggregateCommandHandler->handleMoveNodeAggregate(
                new MoveNodeAggregate(
                    $subject->getContentStreamIdentifier(),
                    $subject->getDimensionSpacePoint(),
                    $subject->getNodeAggregateIdentifier(),
                    $hasEqualParentNode ? null : $this->getParentNode()->getNodeAggregateIdentifier(),
                    null,
                    null,
                    RelationDistributionStrategy::gatherAll(),
                    $this->getInitiatingUserIdentifier()
                )
            )->blockUntilProjectionsAreUpToDate();

            $updateParentNodeInfo = new UpdateNodeInfo();
            $updateParentNodeInfo->setNode($this->getParentNode());

            $this->feedbackCollection->add($updateParentNodeInfo);

            $removeNode = new RemoveNode($subject, $this->getParentNode());
            $this->feedbackCollection->add($removeNode);

            $this->finish($subject);
        }
    }
}
