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
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantSelectionStrategy;
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
     * Checks whether this change can be applied to the subject
     *
     * @return boolean
     */
    public function canApply()
    {
        return true;
    }

    /**
     * Applies this change
     *
     * @return void
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    public function apply()
    {
        if ($this->canApply()) {
            $node = $this->getSubject();
            $parentNode = $node->findParentNode();

            // we have to schedule an the update workspace info before we actually delete the node; otherwise we cannot find the parent nodes anymore.
            $this->updateWorkspaceInfo();

            $command = new RemoveNodeAggregate(
                $node->getContentStreamIdentifier(),
                $node->getNodeAggregateIdentifier(),
                $node->getDimensionSpacePoint(),
                NodeVariantSelectionStrategy::allSpecializations()
            );

            $this->nodeAggregateCommandHandler->handleRemoveNodeAggregate($command)->blockUntilProjectionsAreUpToDate();

            $removeNode = new RemoveNode($node, $parentNode);
            $this->feedbackCollection->add($removeNode);

            $updateParentNodeInfo = new UpdateNodeInfo();
            $updateParentNodeInfo->setNode($parentNode);

            $this->feedbackCollection->add($updateParentNodeInfo);
        }
    }
}
