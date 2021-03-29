<?php
namespace Neos\ContentRepository\Intermediary\Domain\Command;

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Traits\CommonCreateNodeAggregateWithNodeTrait;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * CreateNodeAggregateWithNode
 *
 * Creates a new node aggregate with a new node in the given `contentStreamIdentifier`
 * with the given `nodeAggregateIdentifier` and `originDimensionSpacePoint`.
 * The node will be appended as child node of the given `parentNodeIdentifier` which must cover the given
 * `originDimensionSpacePoint`.
 *
 * @Flow\Proxy(false)
 */
final class CreateNodeAggregateWithNode
{
    use CommonCreateNodeAggregateWithNodeTrait;

    /**
     * The node's initial property values. Will be merged over the node type's default property values
     */
    private ?PropertyValuesToWrite $initialPropertyValues;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        UserIdentifier $initiatingUserIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier = null,
        ?NodeName $nodeName = null,
        ?PropertyValuesToWrite $initialPropertyValues = null,
        ?NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->parentNodeAggregateIdentifier = $parentNodeAggregateIdentifier;
        $this->succeedingSiblingNodeAggregateIdentifier = $succeedingSiblingNodeAggregateIdentifier;
        $this->nodeName = $nodeName;
        $this->initialPropertyValues = $initialPropertyValues ?: PropertyValuesToWrite::fromArray([]);
        $this->tetheredDescendantNodeAggregateIdentifiers = $tetheredDescendantNodeAggregateIdentifiers ?: new NodeAggregateIdentifiersByNodePaths([]);
    }

    public function withInitialPropertyValues(PropertyValuesToWrite $newInitialPropertyValues): self
    {
        return new self(
            $this->contentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->initiatingUserIdentifier,
            $this->parentNodeAggregateIdentifier,
            $this->succeedingSiblingNodeAggregateIdentifier,
            $this->nodeName,
            $newInitialPropertyValues,
            $this->tetheredDescendantNodeAggregateIdentifiers
        );
    }

    /**
     * @return PropertyValuesToWrite|null
     */
    public function getInitialPropertyValues(): ?PropertyValuesToWrite
    {
        return $this->initialPropertyValues;
    }
}
