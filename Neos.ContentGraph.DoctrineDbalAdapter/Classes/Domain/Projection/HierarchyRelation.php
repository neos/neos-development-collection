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

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeName;

/**
 * The active record for reading and writing hierarchy relations from and to the database
 */
class HierarchyRelation
{
    /**
     * @var NodeRelationAnchorPoint
     */
    public $parentNodeAnchor;

    /**
     * @var NodeRelationAnchorPoint
     */
    public $childNodeAnchor;

    /**
     * @var NodeName
     */
    public $name;

    /**
     * @var ContentStreamIdentifier
     */
    public $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    public $dimensionSpacePoint;

    /**
     * @var string
     */
    public $dimensionSpacePointHash;

    /**
     * @var int
     */
    public $position;

    /**
     * @param NodeRelationAnchorPoint $parentNodeAnchor
     * @param NodeRelationAnchorPoint $childNodeAnchor
     * @param NodeName $name
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param string $dimensionSpacePointHash
     * @param int $position
     */
    public function __construct(
        NodeRelationAnchorPoint $parentNodeAnchor,
        NodeRelationAnchorPoint $childNodeAnchor,
        ?NodeName $name,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        string $dimensionSpacePointHash,
        int $position
    ) {
        $this->parentNodeAnchor = $parentNodeAnchor;
        $this->childNodeAnchor = $childNodeAnchor;
        $this->name = $name;
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->dimensionSpacePointHash = $dimensionSpacePointHash;
        $this->position = $position;
    }

    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert('neos_contentgraph_hierarchyrelation', [
            'parentnodeanchor' => $this->parentNodeAnchor,
            'childnodeanchor' => $this->childNodeAnchor,
            'name' => $this->name,
            'contentstreamidentifier' => $this->contentStreamIdentifier,
            'dimensionspacepoint' => json_encode($this->dimensionSpacePoint),
            'dimensionspacepointhash' => $this->dimensionSpacePointHash,
            'position' => $this->position
        ]);
    }

    /**
     * @param Connection $databaseConnection
     */
    public function removeFromDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->delete('neos_contentgraph_hierarchyrelation', $this->getDatabaseIdentifier());
    }

    /**
     * @param NodeRelationAnchorPoint $childAnchorPoint
     * @param Connection $databaseConnection
     */
    public function assignNewChildNode(NodeRelationAnchorPoint $childAnchorPoint, Connection $databaseConnection): void
    {
        $databaseConnection->update(
            'neos_contentgraph_hierarchyrelation',
            [
                'childnodeanchor' => $childAnchorPoint
            ],
            $this->getDatabaseIdentifier()
        );
    }

    /**
     * @param NodeRelationAnchorPoint $parentAnchorPoint
     * @param Connection $databaseConnection
     */
    public function assignNewParentNode(NodeRelationAnchorPoint $parentAnchorPoint, Connection $databaseConnection): void
    {
        $databaseConnection->update(
            'neos_contentgraph_hierarchyrelation',
            [
                'parentnodeanchor' => $parentAnchorPoint
            ],
            $this->getDatabaseIdentifier()
        );
    }
    /**
     * @param int $position
     * @param Connection $databaseConnection
     */
    public function assignNewPosition(int $position, Connection $databaseConnection): void
    {
        $databaseConnection->update(
            'neos_contentgraph_hierarchyrelation',
            [
                'position' => $position
            ],
            $this->getDatabaseIdentifier()
        );
    }

    /**
     * @return array
     */
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
