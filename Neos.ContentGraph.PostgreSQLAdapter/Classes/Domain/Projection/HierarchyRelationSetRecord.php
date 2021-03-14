<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * The active record for reading and writing hierarchy relations from and to the database
 *
 * @Flow\Proxy(false)
 */
final class HierarchyRelationSetRecord
{
    const TABLE_NAME = 'neos_contentgraph_hierarchyrelationset';

    public ContentStreamIdentifier $contentStreamIdentifier;

    public NodeRelationAnchorPoint $parentNodeAnchor;

    /**
     * The child node relation anchor points, indexed by
     * 1. dimension space point hash
     * 2. name or anchor point
     *
     * @var array|NodeRelationAnchorPoint[][]
     */
    public array $childNodeAnchorPoints;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $parentNodeAnchor,
        array $childNodeAnchorPoints
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->parentNodeAnchor = $parentNodeAnchor;
        $this->childNodeAnchorPoints = $childNodeAnchorPoints;
    }

    public static function fromDatabaseRow(array $databaseRow): self
    {
        $childNodeAnchorPoints = [];
        foreach (json_decode($databaseRow['childnodeanchorpoints'], true) as $dimensionSpacePointHash => $rawChildNodeAnchorPointsByName) {
            foreach ($rawChildNodeAnchorPointsByName as $name => $rawChildNodeAnchorPoint) {
                $childNodeAnchorPoints[$dimensionSpacePointHash][$name] = NodeRelationAnchorPoint::fromString($rawChildNodeAnchorPoint);
            }
        }

        return new self(
            ContentStreamIdentifier::fromString($databaseRow['contentstreamidentifier']),
            NodeRelationAnchorPoint::fromString($databaseRow['parentnodeanchor']),
            $childNodeAnchorPoints
        );
    }

    /**
     * @throws DBALException
     */
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert(self::TABLE_NAME, [
            'contentstreamidentifier' => $this->contentStreamIdentifier,
            'parentnodeanchor' => $this->parentNodeAnchor,
            'childnodeanchorpoints' => json_encode($this->childNodeAnchorPoints)
        ]);
    }

    /**
     * @throws DBALException
     */
    public function removeFromDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->delete(self::TABLE_NAME, $this->getDatabaseIdentifier());
    }

    public function getDatabaseIdentifier(): array
    {
        return [
            'contentstreamidentifier' => $this->contentStreamIdentifier,
            'parentnodeanchor' => $this->parentNodeAnchor
        ];
    }
}
