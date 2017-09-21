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
    protected $parentNodeIdentifier;

    /**
     * @var string
     */
    protected $childNodeIdentifier;

    /**
     * @var string
     */
    protected $edgeName;

    /**
     * @var string
     */
    protected $subgraphIdentityHash;

    /**
     * @var array
     */
    protected $subgraphIdentifier;

    /**
     * @var int
     */
    protected $position;


    public function __construct(
        string $parentNodeIdentifier,
        string $childNodeIdentifier,
        string $edgeName = null,
        string $subgraphIdentityHash,
        array $subgraphIdentifier,
        int $position
    ) {
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->childNodeIdentifier = $childNodeIdentifier;
        $this->edgeName = $edgeName;
        $this->subgraphIdentityHash = $subgraphIdentityHash;
        $this->subgraphIdentifier = $subgraphIdentifier;
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getParentNodeIdentifier() : string
    {
        return $this->parentNodeIdentifier;
    }

    /**
     * @return string
     */
    public function getChildNodeIdentifier() : string
    {
        return $this->childNodeIdentifier;
    }

    /**
     * @return string
     */
    public function getEdgeName() : string
    {
        return $this->edgeName;
    }

    /**
     * @return string
     */
    public function getSubgraphIdentityHash() : string
    {
        return $this->subgraphIdentityHash;
    }

    /**
     * @return array
     */
    public function getSubgraphIdentifier() : array
    {
        return $this->subgraphIdentifier;
    }

    /**
     * @return int
     */
    public function getPosition() : int
    {
        return $this->position;
    }


    public function getDatabaseIdentifier(): array
    {
        return [
            'parentnodeidentifier' => $this->parentNodeIdentifier,
            'childnodeidentifier' => $this->childNodeIdentifier,
            'subgraphidentityhash' => $this->subgraphIdentityHash
        ];
    }
}
