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
 * Node references to write
 *
 * As we support property values on the reference this definition object structure will be converted to its Serialized* counterparts.
 * These serializable objects will then be part of the events and persisted commands. See {@see SerializedNodeReferences}
 *
 * @implements \IteratorAggregate<NodeReferencesForName>
 * @api used as part of commands
 */
final readonly class NodeReferencesToWrite implements \IteratorAggregate
{
    /**
     * @var array<string, NodeReferencesForName>
     */
    public array $referencesForName;

    private function __construct(NodeReferencesForName ...$items)
    {
        $referencesForName = [];
        foreach ($items as $item) {
            if (isset($referencesForName[$item->referenceName->value])) {
                throw new \InvalidArgumentException(sprintf('NodeReferencesToWrite does not accept references for the same name %s multiple times.', $item->referenceName->value), 1718193720);
            }
            $referencesForName[$item->referenceName->value] = $item;
        }
        $this->referencesForName = $referencesForName;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public static function create(NodeReferencesForName ...$referencesForName): self
    {
        return new self(...$referencesForName);
    }

    /**
     * @param array<NodeReferencesForName> $references
     */
    public static function fromArray(array $references): self
    {
        return new self(...$references);
    }

    public function withReference(NodeReferencesForName $referencesForName): self
    {
        $references = $this->referencesForName;
        $references[$referencesForName->referenceName->value] = $referencesForName;
        return new self(...$references);
    }

    public function merge(NodeReferencesToWrite $other): self
    {
        return new self(...array_merge($this->referencesForName, $other->referencesForName));
    }

    public function getIterator(): \Traversable
    {
        yield from array_values($this->referencesForName);
    }

    public function isEmpty(): bool
    {
        return count($this->referencesForName) === 0;
    }
}
