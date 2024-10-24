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

use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * A collection of all references for a ReferenceName
 *
 * @internal implementation detail of {@see SerializedNodeReferences}
 */
class SerializedNodeReferencesForName
{
    /**
     * @param ReferenceName $referenceName
     * @param SerializedNodeReference[] $references
     */
    private function __construct(
        public ReferenceName $referenceName,
        public array $references
    ) {
        $existingTargets = [];
        foreach ($references as $reference) {
            if (isset($existingTargets[$reference->targetNodeAggregateId->value])) {
                throw new \InvalidArgumentException(sprintf('Duplicate entry in references to write. Target "%s" already exists in collection.', $reference->targetNodeAggregateId->value), 1700150910);
            }
            $existingTargets[$reference->targetNodeAggregateId->value] = true;
        }
    }

    /**
     * @param ReferenceName $referenceName
     * @param SerializedNodeReference[] $references
     * @return self
     */
    public static function fromNameAndSerializedReferences(ReferenceName $referenceName, array $references): self
    {
        return new self($referenceName, array_values($references));
    }

    /**
     * @param array{"referenceName": string, "references": array<array{"target": string, "properties"?: array<string, mixed>}>} $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ReferenceName::fromString($array['referenceName']),
            array_map(static fn(array $reference) => SerializedNodeReference::fromArray($reference), array_values($array['references']))
        );
    }

    /**
     * @return array{"referenceName": string, "references": array<array{"target": string, "properties"?: mixed}>}
     */
    public function toArray(): array
    {
        return [
            "referenceName" => $this->referenceName->value,
            "references" => array_map(static fn(SerializedNodeReference $reference) => $reference->toArray(), $this->references)
        ];
    }

    public function count(): int
    {
        return count($this->references);
    }
}
