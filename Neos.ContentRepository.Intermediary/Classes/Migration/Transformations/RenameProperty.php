<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Migration\Transformations;
/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * Remove the property
 */
class RenameProperty implements NodeBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    /**
     * Property name to change
     */
    protected string $oldPropertyName;

    /**
     * New name of property
     */
    protected string $newPropertyName;

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    /**
     * Sets the name of the property to change.
     * @param string $oldPropertyName
     */
    public function setFrom(string $oldPropertyName): void
    {
        $this->oldPropertyName = $oldPropertyName;
    }

    /**
     * Sets the new name for the property to change.
     * @param string $newPropertyName
     */
    public function setTo(string $newPropertyName): void
    {
        $this->newPropertyName = $newPropertyName;
    }

    /**
     * Remove the property from the given node.
     *
     * @param NodeInterface $node
     * @param ContentStreamIdentifier $contentStreamForWriting
     * @return CommandResult
     */
    public function execute(NodeInterface $node, DimensionSpacePointSet $coveredDimensionSpacePoints, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        if ($node->hasProperty($this->oldPropertyName)) {
            return $this->nodeAggregateCommandHandler->handleSetSerializedNodeProperties(new SetSerializedNodeProperties(
                $contentStreamForWriting,
                $node->getNodeAggregateIdentifier(),
                $node->getOriginDimensionSpacePoint(),
                SerializedPropertyValues::fromArray([
                    $this->newPropertyName => $node->getProperties()->getProperty($this->oldPropertyName),
                    $this->oldPropertyName => null
                ]),
                UserIdentifier::forSystemUser()
            ));
        }

        return CommandResult::createEmpty();
    }
}
