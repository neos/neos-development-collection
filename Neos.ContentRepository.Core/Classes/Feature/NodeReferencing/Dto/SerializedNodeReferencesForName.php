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
 * A collection of all references for a ReferenceName
 *
 * @internal implementation detail of {@see SerializedNodeReferences}
 */
final readonly class SerializedNodeReferencesForName implements \JsonSerializable
{
    /**
     * @var array<SerializedNodeReference>
     */
    public array $references;

    private function __construct(
        public ReferenceName $referenceName,
        SerializedNodeReference ...$items
    ) {
        $referencesByTarget = [];
        foreach ($items as $item) {
            if (isset($referencesByTarget[$item->targetNodeAggregateId->value])) {
                throw new \InvalidArgumentException(sprintf('Duplicate entry in references to write. Target "%s" already exists in collection.', $item->targetNodeAggregateId->value), 1700150910);
            }
            $referencesByTarget[$item->targetNodeAggregateId->value] = true;
        }
        $this->references = array_values($items);
    }

    /**
     * @param ReferenceName $referenceName
     * @param SerializedNodeReference[] $references
     * @return self
     */
    public static function fromSerializedReferences(ReferenceName $referenceName, array $references): self
    {
        return new self($referenceName, ...$references);
    }

    public static function fromTargets(ReferenceName $referenceName, NodeAggregateIds $nodeAggregateIds): self
    {
        $references = array_map(SerializedNodeReference::fromTarget(...), iterator_to_array($nodeAggregateIds));
        return new self($referenceName, ...$references);
    }

    /**
     * @param array{"referenceName": string, "references": array<array{"target": string, "properties"?: array<string, mixed>}>} $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ReferenceName::fromString($array['referenceName']),
            ...array_map(static fn(array $reference) => SerializedNodeReference::fromArray($reference), array_values($array['references']))
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            "referenceName" => $this->referenceName,
            "references" => $this->references
        ];
    }

    public function count(): int
    {
        return count($this->references);
    }
}
