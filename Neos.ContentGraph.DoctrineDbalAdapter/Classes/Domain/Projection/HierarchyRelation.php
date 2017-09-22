<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

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
 * Simple data model for writing hierarchy relations to the database
 */
class HierarchyRelation
{
    /**
     * @var string
     */
    protected $parentNodeAnchor;

    /**
     * @var string
     */
    protected $childNodeAnchor;

    /**
     * @var string
     */
    protected $name;

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
     * @param string $parentNodeAnchor
     * @param string $childNodeAnchor
     * @param string $name
     * @param string $contentStreamIdentifier
     * @param array $dimensionSpacePoint
     * @param string $dimensionSpacePointHash
     * @param int $position
     */
    public function __construct(string $parentNodeAnchor, string $childNodeAnchor, string $name, string $contentStreamIdentifier, array $dimensionSpacePoint, string $dimensionSpacePointHash, int $position)
    {
        $this->parentNodeAnchor = $parentNodeAnchor;
        $this->childNodeAnchor = $childNodeAnchor;
        $this->name = $name;
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->dimensionSpacePointHash = $dimensionSpacePointHash;
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getParentNodeAnchor(): string
    {
        return $this->parentNodeAnchor;
    }

    /**
     * @return string
     */
    public function getChildNodeAnchor(): string
    {
        return $this->childNodeAnchor;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
            'parentnodeanchor' => $this->parentNodeAnchor,
            'childnodeanchor' => $this->childNodeAnchor,
            'contentstreamidentifier' => $this->contentStreamIdentifier,
            'dimensionspacepointhash' => $this->dimensionSpacePointHash
        ];
    }
}
