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

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Dto;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * A single node references to write
 *
 * Simple:
 *   Just a node aggregate id as target {@see fromTarget}
 *
 * With properties:
 *   Additionally to the target also properties can be specified to be set on the references by using {@see PropertyValuesToWrite} in {@see fromTargetAndProperties}.
 *   We expect the value types to match the configured types of the NodeType
 *
 * Will be converted to {@see SerializedNodeReferences} inside the events and persisted commands.
 *
 * @api used as part of commands
 */
final readonly class NodeReferenceToWrite
{
    private function __construct(
        public NodeAggregateId $targetNodeAggregateId,
        public PropertyValuesToWrite $properties
    ) {
    }

    /**
     * The node aggregate id as target of the reference to write
     *
     * To set a collection of node aggregate ids as targets see {@see NodeReferencesForName::fromTargets()} as utility
     */
    public static function fromTarget(NodeAggregateId $target): self
    {
        return new self($target, PropertyValuesToWrite::createEmpty());
    }

    public static function fromTargetAndProperties(NodeAggregateId $target, PropertyValuesToWrite $properties): self
    {
        return new self($target, $properties);
    }
}
