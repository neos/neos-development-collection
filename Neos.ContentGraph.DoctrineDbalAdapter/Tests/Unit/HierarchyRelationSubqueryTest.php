<?php

declare(strict_types=1);

namespace Neos\ContentGraph\Tests\Unit;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\HierarchyRelationSubquery;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Dbal\Query\StaticWhereCondition;
use PHPUnit\Framework\TestCase;

class HierarchyRelationSubqueryTest extends TestCase
{
    private ContentGraphTableNames $tableNames;

    public function setUp(): void
    {
        $this->tableNames = ContentGraphTableNames::create(ContentRepositoryId::fromString('testing'));
    }

    /** @test */
    public function onlyWithSingleLayer()
    {
        $hierarchyRelationStatement = HierarchyRelationSubquery::create($this->tableNames, ContentStreamLayers::fromArray([1]));

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
              WHERE (h.contentstreamlayer IN (:contentStreamLayers))
                AND NOT EXISTS (
                  SELECT 1
                    FROM cr_testing_p_graph_hierarchyrelation hWin
                    WHERE hWin.id = h.id
                      AND hWin.contentstreamlayer IN (:contentStreamLayers)
                      AND hWin.contentstreamlayer > h.contentstreamlayer
                )
            )
            SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function withSingleDimensionSpacePoint()
    {
        $hierarchyRelationStatement = HierarchyRelationSubquery::create($this->tableNames, ContentStreamLayers::fromArray([1, 3]))
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
              WHERE (h.contentstreamlayer IN (:contentStreamLayers))
                AND id IN (
                  SELECT id FROM cr_testing_p_graph_hierarchyrelation AS h
                    WHERE h.dimensionspacepointhash = :dimensionSpacePointHash
                )
                AND NOT EXISTS (
                  SELECT 1
                    FROM cr_testing_p_graph_hierarchyrelation hWin
                    WHERE hWin.id = h.id
                      AND hWin.contentstreamlayer IN (:contentStreamLayers)
                      AND hWin.contentstreamlayer > h.contentstreamlayer
                )
              AND h.dimensionspacepointhash = :dimensionSpacePointHash
            )
            SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function withSingleDimensionSpacePointAndChildNodeAnchor()
    {
        $hierarchyRelationStatement = HierarchyRelationSubquery::create($this->tableNames, ContentStreamLayers::fromArray([1, 3]))
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
              WHERE (h.contentstreamlayer IN (:contentStreamLayers))
                AND id IN (
                  SELECT id FROM cr_testing_p_graph_hierarchyrelation AS h
                    WHERE h.dimensionspacepointhash = :dimensionSpacePointHash
                    AND h.childnodeanchor = :childNodeRelationAnchorPoint
                )
                AND NOT EXISTS (
                  SELECT 1
                    FROM cr_testing_p_graph_hierarchyrelation hWin
                    WHERE hWin.id = h.id
                      AND hWin.contentstreamlayer IN (:contentStreamLayers)
                      AND hWin.contentstreamlayer > h.contentstreamlayer
                )
              AND h.dimensionspacepointhash = :dimensionSpacePointHash
              AND h.childnodeanchor = :childNodeRelationAnchorPoint
            )
            SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function withArbitraryWhereCondition()
    {
        $hierarchyRelationStatement = HierarchyRelationSubquery::create($this->tableNames, ContentStreamLayers::fromArray([1]))
            ->withWhereCondition(StaticWhereCondition::fromString('h', 'h.subtreetag = :myOwnParameter'));

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
          WHERE (h.contentstreamlayer IN (:contentStreamLayers))
            AND NOT EXISTS (
              SELECT 1
                FROM cr_testing_p_graph_hierarchyrelation hWin
                WHERE hWin.id = h.id
                  AND hWin.contentstreamlayer IN (:contentStreamLayers)
                  AND hWin.contentstreamlayer > h.contentstreamlayer
            )
          AND h.subtreetag = :myOwnParameter
        )
        SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function withArbitraryPossibleWhereCondition()
    {
        $hierarchyRelationStatement = HierarchyRelationSubquery::create($this->tableNames, ContentStreamLayers::fromArray([1]))
            ->withPossibleWhereCondition(StaticWhereCondition::fromString('h', 'h.childnodeanchor = :originalNodeAnchor OR h.parentnodeanchor = :originalNodeAnchor'));

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
          WHERE (h.contentstreamlayer IN (:contentStreamLayers))
            AND id IN (
              SELECT id FROM cr_testing_p_graph_hierarchyrelation AS h
                WHERE h.childnodeanchor = :originalNodeAnchor OR h.parentnodeanchor = :originalNodeAnchor
            )
            AND NOT EXISTS (
              SELECT 1
                FROM cr_testing_p_graph_hierarchyrelation hWin
                WHERE hWin.id = h.id
                  AND hWin.contentstreamlayer IN (:contentStreamLayers)
                  AND hWin.contentstreamlayer > h.contentstreamlayer
            )
        )
        SQL,
            $hierarchyRelationStatement->toSql()
        );
    }
}
