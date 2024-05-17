<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\NodeType;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * @api
 * @implements \IteratorAggregate<TetheredNodeTypeDefinition>
 */
final class TetheredNodeTypeDefinitions implements \IteratorAggregate
{
    /**
     * @var array<string, TetheredNodeTypeDefinition>
     */
    private array $tetheredNodeTypeDefinitions;

    private function __construct(TetheredNodeTypeDefinition ...$tetheredNodeTypeDefinitions)
    {
        /** @var array<string, TetheredNodeTypeDefinition> $tetheredNodeTypeDefinitions */
        $this->tetheredNodeTypeDefinitions = $tetheredNodeTypeDefinitions;
    }

    /**
     * @param array<TetheredNodeTypeDefinition> $tetheredNodeTypeDefinitions
     */
    public static function fromArray(array $tetheredNodeTypeDefinitions): self
    {
        $tetheredNodeTypeDefinitionDefinitionByName = [];
        foreach ($tetheredNodeTypeDefinitions as $index => $tetheredNodeTypeDefinitionDefinition) {
            $tetheredNodeTypeDefinitionDefinition instanceof TetheredNodeTypeDefinition || throw new \InvalidArgumentException(sprintf('expected instance of %s, got: %s at index %s', TetheredNodeTypeDefinition::class, get_debug_type($tetheredNodeTypeDefinitionDefinition), $index), 1713549511);
            !array_key_exists($tetheredNodeTypeDefinitionDefinition->name->value, $tetheredNodeTypeDefinitionDefinitionByName) || throw new \InvalidArgumentException(sprintf('Tethered node type definition with name "%s" is already registered at index %s', $tetheredNodeTypeDefinitionDefinition->name->value, $index), 1713549527);
            $tetheredNodeTypeDefinitionDefinitionByName[$tetheredNodeTypeDefinitionDefinition->name->value] = $tetheredNodeTypeDefinitionDefinition;
        }
        return new self(...$tetheredNodeTypeDefinitionDefinitionByName);
    }

    public function contain(NodeName|string $nodeName): bool
    {
        if ($nodeName instanceof NodeName) {
            $nodeName = $nodeName->value;
        }
        return array_key_exists($nodeName, $this->tetheredNodeTypeDefinitions);
    }

    public function get(NodeName|string $nodeName): ?TetheredNodeTypeDefinition
    {
        if ($nodeName instanceof NodeName) {
            $nodeName = $nodeName->value;
        }
        return $this->tetheredNodeTypeDefinitions[$nodeName] ?? null;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->tetheredNodeTypeDefinitions;
    }

    /**
     * @return array<TetheredNodeTypeDefinition>
     */
    public function toArray(): array
    {
        return $this->tetheredNodeTypeDefinitions;
    }
}
