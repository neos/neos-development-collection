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

namespace Neos\ContentRepository\Feature\NodeCreation\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\NodeAggregateIdentifiersByNodePaths;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * CreateNodeAggregateWithNode
 *
 * Creates a new node aggregate with a new node in the given `contentStreamIdentifier`
 * with the given `nodeAggregateIdentifier` and `originDimensionSpacePoint`.
 * The node will be appended as child node of the given `parentNodeIdentifier` which must cover the given
 * `originDimensionSpacePoint`.
 */
final class CreateNodeAggregateWithNode implements CommandInterface
{
    /**
     * Node aggregate identifier of the node's succeeding sibling (optional)
     * If not given, the node will be added as the parent's first child
     */
    public readonly ?NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier;

    /**
     * The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     */
    public readonly ?NodeName $nodeName;

    /**
     * The node's initial property values. Will be merged over the node type's default property values
     */
    public readonly PropertyValuesToWrite $initialPropertyValues;

    /**
     * NodeAggregateIdentifiers for tethered descendants (optional).
     *
     * If the given node type declares tethered child nodes, you may predefine their node aggregate identifiers
     * using this assignment registry.
     * Since tethered child nodes may have tethered child nodes themselves,
     * this registry is indexed using relative node paths to the node to create in the first place.
     */
    public readonly NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers;

    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly NodeTypeName $nodeTypeName,
        /**
         * Origin of the new node in the dimension space.
         * Will also be used to calculate a set of dimension points where the new node will cover
         * from the configured specializations.
         */
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly UserIdentifier $initiatingUserIdentifier,
        public readonly NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier = null,
        ?NodeName $nodeName = null,
        ?PropertyValuesToWrite $initialPropertyValues = null,
        ?NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers = null
    ) {
        $this->succeedingSiblingNodeAggregateIdentifier = $succeedingSiblingNodeAggregateIdentifier;
        $this->nodeName = $nodeName;
        $this->initialPropertyValues = $initialPropertyValues ?: PropertyValuesToWrite::fromArray([]);
        $this->tetheredDescendantNodeAggregateIdentifiers = $tetheredDescendantNodeAggregateIdentifiers
            ?: new NodeAggregateIdentifiersByNodePaths([]);
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
}
