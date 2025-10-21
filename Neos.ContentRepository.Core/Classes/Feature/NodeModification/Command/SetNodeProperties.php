<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeModification\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Add property values for a given node.
 *
 * The properties will not be replaced but will be merged via the existing ones by the projection.
 * A null value will cause to unset a nodes' property.
 *
 * The property values support arbitrary types (but must match the NodeType's property types -
 * this is validated in the command handler).
 *
 * Internally, this object is converted into a {@see SetSerializedNodeProperties} command, which is
 * then processed and stored.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class SetNodeProperties implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the set properties operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate to set the properties for
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint The dimension space point the properties should be changed in
     * @param PropertyValuesToWrite $propertyValues Names and (unserialized) values of properties to set, or unset if the value is null
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public PropertyValuesToWrite $propertyValues,
    ) {
        if ($this->propertyValues->isEmpty()) {
            throw new \InvalidArgumentException(sprintf('The command "SetNodeProperties" for node %s must contain property values', $this->nodeAggregateId->value), 1733394351);
        }
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the set properties operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate to set the properties for
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint The dimension space point the properties should be changed in
     * @param PropertyValuesToWrite $propertyValues Names and (unserialized) values of properties to set, or unset if the value is null
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $originDimensionSpacePoint, PropertyValuesToWrite $propertyValues): self
    {
        return new self($workspaceName, $nodeAggregateId, $originDimensionSpacePoint, $propertyValues);
    }

    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint']),
            PropertyValuesToWrite::fromArray($array['propertyValues']),
        );
    }
}
