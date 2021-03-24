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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
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

    public DimensionSpacePoint $dimensionSpacePoint;

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
        DimensionSpacePoint $dimensionSpacePoint,
        array $childNodeAnchorPoints
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->parentNodeAnchor = $parentNodeAnchor;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
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
            DimensionSpacePoint::fromArray(json_decode($databaseRow['dimensionspacepoint'])),
            $childNodeAnchors
        );
    }

    public function getDimensionSpacePoints(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function addChildNodeAnchor(
        NodeRelationAnchorPoint $childNodeAnchor,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchor,
        Connection $databaseConnection
    ): void {
        $childNodeAnchors = $this->childNodeAnchors;
        if ($succeedingSiblingAnchor) {
            $pivot = array_search($succeedingSiblingAnchor, $childNodeAnchors);
            array_splice($childNodeAnchors, $pivot, 0, $childNodeAnchor);
        } else {
            $childNodeAnchors[] = $childNodeAnchor;
        }

        $databaseConnection->update(
            self::TABLE_NAME,
            [
                'childnodeanchors' => \json_encode($childNodeAnchors)
            ],
            $this->getDatabaseIdentifier()
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
            'dimensionspacepoint' => \json_encode($this->dimensionSpacePoint),
            'dimensionspacepointhash' => $this->dimensionSpacePoint->getHash(),
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
            'contentstreamidentifier' => (string)$this->contentStreamIdentifier,
            'parentnodeanchor' => (string)$this->parentNodeAnchor,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->getHash()
        ];
    }
}
