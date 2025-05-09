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

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * Node references to write for a specific reference name
 *
 * Will be converted to {@see SerializedNodeReferencesForName} inside the events and persisted commands.
 *
 * @api used as part of commands
 */
final readonly class NodeReferencesForName
{
    /**
     * @var array<NodeReferenceToWrite>
     */
    public array $references;

    private function __construct(
        public ReferenceName $referenceName,
        NodeReferenceToWrite ...$references
    ) {
        $referencesByTarget = [];
        foreach ($references as $reference) {
            if (isset($referencesByTarget[$reference->targetNodeAggregateId->value])) {
                throw new \InvalidArgumentException(sprintf('Duplicate entry in references to write. Target "%s" already exists in collection.', $reference->targetNodeAggregateId->value), 1730365958);
            }
            $referencesByTarget[$reference->targetNodeAggregateId->value] = true;
        }
        $this->references = $references;
    }

    /**
     * As the previously set references will be replaced by writing new references specifying
     * no references for a name will delete the previous ones
     */
    public static function createEmpty(ReferenceName $name): self
    {
        return new self($name, ...[]);
    }

    public static function fromTargets(ReferenceName $name, NodeAggregateIds $nodeAggregateIds): self
    {
        $references = array_map(NodeReferenceToWrite::fromTarget(...), iterator_to_array($nodeAggregateIds));
        return new self($name, ...$references);
    }

    /**
     * @param NodeReferenceToWrite[] $references
     */
    public static function fromReferences(ReferenceName $name, array $references): self
    {
        return new self($name, ...$references);
    }
}
