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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * The active record for reading and writing hierarchy hyperrelations from and to the database
 *
 * @Flow\Proxy(false)
 */
final class HierarchyHyperrelationRecord
{
    const TABLE_NAME = 'neos_contentgraph_hierarchyhyperrelation';

    public ContentStreamIdentifier $contentStreamIdentifier;

    public DimensionSpacePointSet $dimensionSpacePoints;

    public NodeRelationAnchorPoint $parentNodeAnchor;

    /**
     * The child node relation anchor points, indexed by sorting position
     *
     * @var array|NodeRelationAnchorPoint[]
     */
    public array $childNodeAnchors;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $parentNodeAnchor,
        DimensionSpacePointSet $dimensionSpacePoints,
        array $childNodeAnchorPoints
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->parentNodeAnchor = $parentNodeAnchor;
        $this->dimensionSpacePoints = $dimensionSpacePoints;
        $this->childNodeAnchors = $childNodeAnchorPoints;
    }

    public static function fromDatabaseRow(array $databaseRow): self
    {
        $childNodeAnchors = array_map(function (string $childNodeAnchor) {
            return NodeRelationAnchorPoint::fromString($childNodeAnchor);
        }, $databaseRow['childnodeanchors'] ? json_decode($databaseRow['childnodeanchors'], true) : []);

        return new self(
            ContentStreamIdentifier::fromString($databaseRow['contentstreamidentifier']),
            NodeRelationAnchorPoint::fromString($databaseRow['parentnodeanchor']),
            DimensionSpacePointSet::fromArray(json_decode($databaseRow['dimensionspacepoints'])),
            $childNodeAnchors
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
            'dimensionspacepoints' => \json_encode($this->dimensionSpacePoints),
            'dimensionspacepointhashes' => \json_encode($this->dimensionSpacePoints->getPointHashes()),
            'childnodeanchors' => \json_encode($this->childNodeAnchors)
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
