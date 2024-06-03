<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeCreation\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A node aggregate with its initial node was created
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class NodeAggregateWithNodeWasCreated implements
    EventInterface,
    PublishableToWorkspaceInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        public NodeTypeName $nodeTypeName,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public InterdimensionalSiblings $succeedingSiblingsForCoverage,
        public NodeAggregateId $parentNodeAggregateId,
        public ?NodeName $nodeName,
        public SerializedPropertyValues $initialPropertyValues,
        public NodeAggregateClassification $nodeAggregateClassification,
    ) {
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function withWorkspaceNameAndContentStreamId(WorkspaceName $targetWorkspaceName, ContentStreamId $contentStreamId): self
    {
        return new self(
            $targetWorkspaceName,
            $contentStreamId,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->succeedingSiblingsForCoverage,
            $this->parentNodeAggregateId,
            $this->nodeName,
            $this->initialPropertyValues,
            $this->nodeAggregateClassification,
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            NodeTypeName::fromString($values['nodeTypeName']),
            OriginDimensionSpacePoint::fromArray($values['originDimensionSpacePoint']),
            array_key_exists('succeedingSiblingsForCoverage', $values)
                ? InterdimensionalSiblings::fromArray($values['succeedingSiblingsForCoverage'])
                : InterdimensionalSiblings::fromDimensionSpacePointSetWithSingleSucceedingSiblings(
                    DimensionSpacePointSet::fromArray($values['coveredDimensionSpacePoints']),
                    isset($values['succeedingNodeAggregateId'])
                        ? NodeAggregateId::fromString($values['succeedingNodeAggregateId'])
                        : null,
                ),
            NodeAggregateId::fromString($values['parentNodeAggregateId']),
            isset($values['nodeName']) ? NodeName::fromString($values['nodeName']) : null,
            SerializedPropertyValues::fromArray($values['initialPropertyValues']),
            NodeAggregateClassification::from($values['nodeAggregateClassification']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
