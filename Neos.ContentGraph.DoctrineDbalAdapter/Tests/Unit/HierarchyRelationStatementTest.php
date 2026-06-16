<?php

declare(strict_types=1);

namespace Neos\ContentGraph\Tests\Unit;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\HierarchyRelationStatement;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use PHPUnit\Framework\TestCase;

class HierarchyRelationStatementTest extends TestCase
{
    private ContentGraphTableNames $tableNames;

    public function setUp(): void
    {
        $this->tableNames = ContentGraphTableNames::create(ContentRepositoryId::fromString('testing'));
    }

    /** @test */
    public function onlyWithSingleLayer()
    {
        $hierarchyRelationStatement = HierarchyRelationStatement::create($this->tableNames, ContentStreamLayers::fromArray([1]));

        self::assertSame(
            ['contentStreamLayers' => [1]],
            $hierarchyRelationStatement->getParameters()->toDbalValues()
        );

        self::assertSame(
            ['contentStreamLayers' => ArrayParameterType::INTEGER],
            $hierarchyRelationStatement->getParameters()->toDbalTypes()
        );

        self::assertEquals(
        <<<SQL
        (SELECT h.*
          FROM cr_testing_p_graph_hierarchyrelation AS h
          INNER JOIN (
            SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
              FROM cr_testing_p_graph_hierarchyrelation
                WHERE (contentstreamlayer IN (:contentStreamLayers))
            GROUP BY id
          ) AS readHierarchy
            ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
        )
        SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function withSingleDimensionSpacePoint()
    {
        $hierarchyRelationStatement = HierarchyRelationStatement::create($this->tableNames, ContentStreamLayers::fromArray([1, 3]))
            ->withDimensionSpacePoint(DimensionSpacePoint::createWithoutDimensions());

        self::assertSame(
            [
                'contentStreamLayers' => [1, 3],
                'dimensionSpacePointHash' => 'd751713988987e9331980363e24189ce',
            ],
            $hierarchyRelationStatement->getParameters()->toDbalValues()
        );

        self::assertSame(
            [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
                'dimensionSpacePointHash' => ParameterType::STRING,
            ],
            $hierarchyRelationStatement->getParameters()->toDbalTypes()
        );

        self::assertEquals(
            <<<SQL
            (SELECT h.*
              FROM cr_testing_p_graph_hierarchyrelation AS h
              INNER JOIN (
                SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
                  FROM cr_testing_p_graph_hierarchyrelation
                    WHERE (contentstreamlayer IN (:contentStreamLayers))
                    AND id IN (
                      SELECT id FROM cr_testing_p_graph_hierarchyrelation AS h
                        WHERE h.dimensionspacepointhash = :dimensionSpacePointHash
                    )
                GROUP BY id
              ) AS readHierarchy
                ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
              WHERE h.dimensionspacepointhash = :dimensionSpacePointHash
            )
            SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function withSingleDimensionSpacePointAndChildNodeAnchor()
    {
        $hierarchyRelationStatement = HierarchyRelationStatement::create($this->tableNames, ContentStreamLayers::fromArray([1, 3]))
            ->withDimensionSpacePoint(DimensionSpacePoint::createWithoutDimensions())
            ->withChildNodeRelationAnchor(NodeRelationAnchorPoint::fromInteger(22));

        self::assertSame(
            [
                'contentStreamLayers' => [1, 3],
                'dimensionSpacePointHash' => 'd751713988987e9331980363e24189ce',
                'childNodeRelationAnchorPoint' => 22,
            ],
            $hierarchyRelationStatement->getParameters()->toDbalValues()
        );

        self::assertSame(
            [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
                'dimensionSpacePointHash' => ParameterType::STRING,
                'childNodeRelationAnchorPoint' => ParameterType::INTEGER,
            ],
            $hierarchyRelationStatement->getParameters()->toDbalTypes()
        );

        self::assertEquals(
            <<<SQL
            (SELECT h.*
              FROM cr_testing_p_graph_hierarchyrelation AS h
              INNER JOIN (
                SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
                  FROM cr_testing_p_graph_hierarchyrelation
                    WHERE (contentstreamlayer IN (:contentStreamLayers))
                    AND id IN (
                      SELECT id FROM cr_testing_p_graph_hierarchyrelation AS h
                        WHERE h.dimensionspacepointhash = :dimensionSpacePointHash
                        AND h.childnodeanchor = :childNodeRelationAnchorPoint
                    )
                GROUP BY id
              ) AS readHierarchy
                ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
              WHERE h.dimensionspacepointhash = :dimensionSpacePointHash
              AND h.childnodeanchor = :childNodeRelationAnchorPoint
            )
            SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function withArbitraryWhereClause()
    {
        $hierarchyRelationStatement = HierarchyRelationStatement::create($this->tableNames, ContentStreamLayers::fromArray([1]))
            ->where('h.subtreetag = :myOwnParameter');

        self::assertSame(
            ['contentStreamLayers' => [1]],
            $hierarchyRelationStatement->getParameters()->toDbalValues()
        );

        self::assertSame(
            ['contentStreamLayers' => ArrayParameterType::INTEGER],
            $hierarchyRelationStatement->getParameters()->toDbalTypes()
        );

        self::assertEquals(
            <<<SQL
        (SELECT h.*
          FROM cr_testing_p_graph_hierarchyrelation AS h
          INNER JOIN (
            SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
              FROM cr_testing_p_graph_hierarchyrelation
                WHERE (contentstreamlayer IN (:contentStreamLayers))
            GROUP BY id
          ) AS readHierarchy
            ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
          WHERE h.subtreetag = :myOwnParameter
        )
        SQL,
            $hierarchyRelationStatement->toSql()
        );
    }
}
