<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Dto;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Simple data model for writing hierarchy edges to the database
 */
class HierarchyEdge
{
    /**
     * @var string
     */
    protected $parentNodesIdentifierInGraph;

    /**
     * @var string
     */
    protected $childNodesIdentifierInGraph;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $subgraphIdentifier;

    /**
     * @var int
     */
    protected $position;


    public function __construct(
        string $parentNodesIdentifierInGraph,
        string $childNodesIdentifierInGraph,
        string $name = null,
        string $subgraphIdentifier,
        int $position
    ) {
        $this->parentNodesIdentifierInGraph = $parentNodesIdentifierInGraph;
        $this->name = $name;
        $this->subgraphIdentifier = $subgraphIdentifier;
        $this->childNodesIdentifierInGraph = $childNodesIdentifierInGraph;
        $this->position = $position;
    }


    public function getParentNodesIdentifierInGraph(): string
    {
        return $this->parentNodesIdentifierInGraph;
    }

    public function getChildNodesIdentifierInGraph(): string
    {
        return $this->childNodesIdentifierInGraph;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    public function getSubgraphIdentifier(): string
    {
        return $this->subgraphIdentifier;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getDatabaseIdentifier(): array
    {
        return [
            'parentnodesidentifieringraph' => $this->parentNodesIdentifierInGraph,
            'childnodesidentifieringraph' => $this->childNodesIdentifierInGraph,
            'subgraphidentifier' => $this->subgraphIdentifier
        ];
    }

    public function toDatabaseArray(): array
    {
        return [
            'parentnodesidentifieringraph' => $this->parentNodesIdentifierInGraph,
            'childnodesidentifieringraph' => $this->childNodesIdentifierInGraph,
            'name' => $this->name,
            'subgraphidentifier' => $this->subgraphIdentifier,
            'position' => $this->position
        ];
    }
}
