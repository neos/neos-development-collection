<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The active record for reading hierarchy from and to the database.
 * The child node relation anchor points are indexed by sorting position.
 *
 * @internal
 */
final readonly class HierarchyRelationRecord
{
    public function __construct(
        public ContentStreamId $contentStreamId,
        public NodeRelationAnchorPoint $parentNodeAnchor,
        public DimensionSpacePoint $dimensionSpacePoint,
        public NodeRelationAnchorPoints $childNodeAnchorPoints
    ) {
    }

    /**
     * @param array<string,string> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            ContentStreamId::fromString($databaseRow['contentstreamid']),
            NodeRelationAnchorPoint::fromString($databaseRow['parentnodeanchor']),
            DimensionSpacePoint::fromJsonString($databaseRow['dimensionspacepoint']),
            NodeRelationAnchorPoints::fromDatabaseString(
                $databaseRow['childnodeanchors']
            )
        );
    }

    /**
     * @return array<string,string>
     */
    public function getDatabaseIdentifier(): array
    {
        return [
            'contentstreamid' => $this->contentStreamId->value,
            'parentnodeanchor' => $this->parentNodeAnchor->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash
        ];
    }

}
