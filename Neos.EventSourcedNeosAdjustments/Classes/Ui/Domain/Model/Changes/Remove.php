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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantSelectionStrategyIdentifier;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedNeosAdjustments\FusionCaching\ContentCacheFlusher;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\AbstractChange;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\RemoveNode;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateNodeInfo;

/**
 * Removes a node
 */
class Remove extends AbstractChange
{
    /**
     * @Flow\Inject
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * @Flow\Inject
     * @var ContentCacheFlusher
     */
    protected $contentCacheFlusher;

    /**
     * Checks whether this change can be applied to the subject
     *
     * @return boolean
     */
    public function canApply(): bool
    {
        return !is_null($this->subject);
    }

    /**
     * Applies this change
     *
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    public function apply(): void
    {
        $subject = $this->subject;
        if ($this->canApply() && !is_null($subject)) {
            $parentNode = $this->findParentNode($subject);
            if (is_null($parentNode)) {
                throw new \InvalidArgumentException(
                    'Cannot apply Remove without a parent on node ' . $subject->getNodeAggregateIdentifier(),
                    1645560717
                );
            }

            // we have to remember what parts of the content cache to flush before we actually delete the node;
            // otherwise we cannot find the parent nodes anymore.
            $doFlushContentCache = $this->contentCacheFlusher->scheduleFlushNodeAggregate(
                $subject->getContentStreamIdentifier(),
                $subject->getNodeAggregateIdentifier()
            );

            // we have to schedule an the update workspace info before we actually delete the node;
            // otherwise we cannot find the parent nodes anymore.
            $this->updateWorkspaceInfo();

            $command = RemoveNodeAggregate::create(
                $subject->getContentStreamIdentifier(),
                $subject->getNodeAggregateIdentifier(),
                $subject->getDimensionSpacePoint(),
                NodeVariantSelectionStrategyIdentifier::STRATEGY_ALL_SPECIALIZATIONS,
                $this->getInitiatingUserIdentifier()
            );
            $closestDocumentParentNode = $this->findClosestDocumentNode($subject);
            if ($closestDocumentParentNode !== null) {
                $command = $command->withRemovalAttachmentPoint(
                    $closestDocumentParentNode->getNodeAggregateIdentifier()
                );
            }

            $this->nodeAggregateCommandHandler->handleRemoveNodeAggregate(
                $command
            )->blockUntilProjectionsAreUpToDate();
            $doFlushContentCache();

            $removeNode = new RemoveNode($subject, $parentNode);
            $this->feedbackCollection->add($removeNode);

            $updateParentNodeInfo = new UpdateNodeInfo();
            $updateParentNodeInfo->setNode($parentNode);

            $this->feedbackCollection->add($updateParentNodeInfo);
        }
    }
}
