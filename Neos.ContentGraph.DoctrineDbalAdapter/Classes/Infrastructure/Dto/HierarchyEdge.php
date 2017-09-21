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
    protected $contentStreamIdentifier;

    /**
     * @var array
     */
    protected $dimensionSpacePoint;

    /**
     * @var string
     */
    protected $dimensionSpacePointHash;

    /**
     * @var int
     */
    protected $position;

    /**
     * HierarchyEdge constructor.
     * @param string $parentNodeIdentifier
     * @param string $childNodeIdentifier
     * @param string $edgeName
     * @param string $contentStreamIdentifier
     * @param array $dimensionSpacePoint
     * @param string $dimensionSpacePointHash
     * @param int $position
     */
    public function __construct(string $parentNodeIdentifier, string $childNodeIdentifier, string $edgeName, string $contentStreamIdentifier, array $dimensionSpacePoint, string $dimensionSpacePointHash, int $position)
    {
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->childNodeIdentifier = $childNodeIdentifier;
        $this->edgeName = $edgeName;
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->dimensionSpacePointHash = $dimensionSpacePointHash;
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getParentNodeIdentifier(): string
    {
        return $this->parentNodeIdentifier;
    }

    /**
     * @return string
     */
    public function getChildNodeIdentifier(): string
    {
        return $this->childNodeIdentifier;
    }

    /**
     * @return string
     */
    public function getEdgeName(): string
    {
        return $this->edgeName;
    }

    /**
     * @return string
     */
    public function getContentStreamIdentifier(): string
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return array
     */
    public function getDimensionSpacePoint(): array
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return string
     */
    public function getDimensionSpacePointHash(): string
    {
        return $this->dimensionSpacePointHash;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }


    public function getDatabaseIdentifier(): array
    {
        return [
            'parentnodeidentifier' => $this->parentNodeIdentifier,
            'childnodeidentifier' => $this->childNodeIdentifier,
            'contentstreamidentifier' => $this->contentStreamIdentifier,
            'dimensionspacepointhash' => $this->dimensionSpacePointHash
        ];
    }
}
