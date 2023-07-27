<?php

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 * The active record for reading and writing nodes from and to the database
 *
 * @internal
 */
final class NodeRecord
{
    public const TABLE_NAME_SUFFIX = '_node';

    public function __construct(
        public NodeRelationAnchorPoint $relationAnchorPoint,
        public NodeAggregateId $nodeAggregateId,
        /** @var array<string,string> */
        public array $originDimensionSpacePoint,
        public string $originDimensionSpacePointHash,
        public SerializedPropertyValues $properties,
        public NodeTypeName $nodeTypeName,
        public NodeAggregateClassification $classification,
        /** Transient node name to store a node name after fetching a node with hierarchy (not always available) */
        public ?NodeName $nodeName,
        public Timestamps $timestamps,
    ) {
    }

    /**
     * @param Connection $databaseConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function addToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->insert($tableNamePrefix . self::TABLE_NAME_SUFFIX, [
            'relationanchorpoint' => $this->relationAnchorPoint->value,
            'nodeaggregateid' => $this->nodeAggregateId->value,
            'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
            'origindimensionspacepointhash' => $this->originDimensionSpacePointHash,
            'properties' => json_encode($this->properties),
            'nodetypename' => $this->nodeTypeName->value,
            'classification' => $this->classification->value,
            'created' => $this->timestamps->created,
            'originalcreated' => $this->timestamps->originalCreated,
            'lastmodified' => $this->timestamps->lastModified,
            'originallastmodified' => $this->timestamps->originalLastModified,
        ], [
            'created' => Types::DATETIME_IMMUTABLE,
            'originalcreated' => Types::DATETIME_IMMUTABLE,
            'lastmodified' => Types::DATETIME_IMMUTABLE,
            'originallastmodified' => Types::DATETIME_IMMUTABLE,
        ]);
    }

    /**
     * @param Connection $databaseConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->update(
            $tableNamePrefix . self::TABLE_NAME_SUFFIX,
            [
                'nodeaggregateid' => $this->nodeAggregateId->value,
                'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
                'origindimensionspacepointhash' => $this->originDimensionSpacePointHash,
                'properties' => json_encode($this->properties),
                'nodetypename' => $this->nodeTypeName->value,
                'classification' => $this->classification->value,
                'lastmodified' => $this->timestamps->lastModified,
                'originallastmodified' => $this->timestamps->originalLastModified,
            ],
            [
                'relationanchorpoint' => $this->relationAnchorPoint->value
            ],
            [
                'lastmodified' => Types::DATETIME_IMMUTABLE,
                'originallastmodified' => Types::DATETIME_IMMUTABLE,
            ]
        );
    }

    /**
     * @param Connection $databaseConnection
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function removeFromDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->delete($tableNamePrefix . self::TABLE_NAME_SUFFIX, [
            'relationanchorpoint' => $this->relationAnchorPoint->value
        ]);
    }

    /**
     * @param array<string,string> $databaseRow
     * @throws \Exception
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            NodeRelationAnchorPoint::fromString($databaseRow['relationanchorpoint']),
            NodeAggregateId::fromString($databaseRow['nodeaggregateid']),
            json_decode($databaseRow['origindimensionspacepoint'], true),
            $databaseRow['origindimensionspacepointhash'],
            SerializedPropertyValues::fromArray(json_decode($databaseRow['properties'], true)),
            NodeTypeName::fromString($databaseRow['nodetypename']),
            NodeAggregateClassification::from($databaseRow['classification']),
            isset($databaseRow['name']) ? NodeName::fromString($databaseRow['name']) : null,
            Timestamps::create(
                self::parseDateTimeString($databaseRow['created']),
                self::parseDateTimeString($databaseRow['originalcreated']),
                isset($databaseRow['lastmodified']) ? self::parseDateTimeString($databaseRow['lastmodified']) : null,
                isset($databaseRow['originallastmodified']) ? self::parseDateTimeString($databaseRow['originallastmodified']) : null,
            ),
        );
    }

    private static function parseDateTimeString(string $string): \DateTimeImmutable
    {
        $result = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $string);
        if ($result === false) {
            throw new \RuntimeException(sprintf('Failed to parse "%s" into a valid DateTime', $string), 1678902055);
        }
        return $result;
    }
}
