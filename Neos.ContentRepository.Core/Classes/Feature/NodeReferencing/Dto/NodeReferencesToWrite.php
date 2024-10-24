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

/**
 * Node references to write, supports arbitrary objects as reference values.
 * Will be then converted to {@see SerializedNodeReferences} inside the events and persisted commands.
 *
 * We expect the value types to match the NodeType's property types (this is validated in the command handler).
 *
 * @implements \IteratorAggregate<NodeReferencesForName>
 * @api used as part of commands
 */
final readonly class NodeReferencesToWrite implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var array<NodeReferencesForName>
     */
    public array $references;

    private function __construct(NodeReferencesForName ...$references)
    {
        $seenNames = [];
        foreach ($references as $reference) {
            $referenceNameExists = isset($seenNames[$reference->referenceName->value]);
            if ($referenceNameExists) {
                throw new \InvalidArgumentException(sprintf('You cannot set references for the same ReferenceName %s multiple times.', $reference->referenceName->value), 1718193720);
            }
            $seenNames[$reference->referenceName->value] = true;
        }
        $this->references = $references;
    }

    /**
     * @param array<NodeReferencesForName> $references
     */
    public static function fromReferences(array $references): self
    {
        return new self(...$references);
    }

    public function merge(NodeReferencesToWrite $nodeReferencesToWrite): self
    {
        return new self(...$this->getIterator(), ...$nodeReferencesToWrite->getIterator());
    }

    /**
     * @return \Traversable<NodeReferencesForName>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->references as $reference) {
            yield $reference;
        }
    }

    public function isEmpty(): bool
    {
        return count($this->references) === 0;
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public function jsonSerialize(): array
    {
        $result = [];
        foreach ($this->references as $reference) {
            $result[$reference->referenceName->value] = array_map(static fn(NodeReferenceToWrite $nodeReference) => $nodeReference->targetAndPropertiesToArray(), $reference->references);
        }
        return $result;
    }
}
