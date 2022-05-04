<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

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
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Projection\Content\PropertyCollectionInterface;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

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
     */
    public function execute(
        NodeInterface $node,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        if ($node->hasProperty($this->oldPropertyName)) {
            /** @var PropertyCollectionInterface $properties */
            $properties = $node->getProperties();
            return $this->nodeAggregateCommandHandler->handleSetSerializedNodeProperties(
                new SetSerializedNodeProperties(
                    $contentStreamForWriting,
                    $node->getNodeAggregateIdentifier(),
                    $node->getOriginDimensionSpacePoint(),
                    SerializedPropertyValues::fromArray([
                        $this->newPropertyName => $properties->serialized()
                            ->getProperty($this->oldPropertyName),
                        $this->oldPropertyName => null
                    ]),
                    UserIdentifier::forSystemUser()
                )
            );
        }

        return CommandResult::createEmpty();
    }
}
