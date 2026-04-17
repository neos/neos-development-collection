<?php

declare(strict_types=1);

namespace Neos\ContentGraph\Tests\Unit;

use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\HierarchyRelationStatement;
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
    public function getSql()
    {
        $hierarchyRelationStatement = HierarchyRelationStatement::for($this->tableNames);

        self::assertEquals(
            <<<SQL
            (SELECT h.*
                FROM cr_testing_p_graph_hierarchyrelation as h
                INNER JOIN (
                    SELECT id, MAX(contentstreamlayer) as contentstreamlayer
                        FROM cr_testing_p_graph_hierarchyrelation
                        WHERE (contentstreamlayer IN (:contentStreamLayers))
                    GROUP BY id
                ) AS activeLayer
                    ON h.id = activeLayer.id AND h.contentstreamlayer = activeLayer.contentstreamlayer
            )
            SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function whereGetSql()
    {
        $hierarchyRelationStatement = HierarchyRelationStatement::for($this->tableNames)
            ->where('h.dimensionspacepointhash in (:dimensionSpacePointHashes)');

        self::assertEquals(
            <<<SQL
            (SELECT h.*
                FROM cr_testing_p_graph_hierarchyrelation as h
                INNER JOIN (
                    SELECT id, MAX(contentstreamlayer) as contentstreamlayer
                        FROM cr_testing_p_graph_hierarchyrelation
                        WHERE (contentstreamlayer IN (:contentStreamLayers))
                    GROUP BY id
                ) AS activeLayer
                    ON h.id = activeLayer.id AND h.contentstreamlayer = activeLayer.contentstreamlayer
                WHERE h.dimensionspacepointhash in (:dimensionSpacePointHashes)
            )
            SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function whereAndWhereGetSql()
    {
        $hierarchyRelationStatement = HierarchyRelationStatement::for($this->tableNames)
            ->where('h.dimensionspacepointhash in (:dimensionSpacePointHashes)')
            ->andWhere('h.childnodeanchor = :anchor');

        self::assertEquals(
            <<<SQL
            (SELECT h.*
                FROM cr_testing_p_graph_hierarchyrelation as h
                INNER JOIN (
                    SELECT id, MAX(contentstreamlayer) as contentstreamlayer
                        FROM cr_testing_p_graph_hierarchyrelation
                        WHERE (contentstreamlayer IN (:contentStreamLayers))
                    GROUP BY id
                ) AS activeLayer
                    ON h.id = activeLayer.id AND h.contentstreamlayer = activeLayer.contentstreamlayer
                WHERE h.dimensionspacepointhash in (:dimensionSpacePointHashes)
                AND h.childnodeanchor = :anchor
            )
            SQL,
            $hierarchyRelationStatement->toSql()
        );
    }

    /** @test */
    public function allContentStreamsGetSql()
    {
        $hierarchyRelationStatement = HierarchyRelationStatement::for($this->tableNames)
            ->allContentStreams();

        self::assertEquals(
            <<<SQL
            (SELECT h.*
                FROM cr_testing_p_graph_hierarchyrelation as h
                INNER JOIN (
                    SELECT id, MAX(contentstreamlayer) as contentstreamlayer
                        FROM cr_testing_p_graph_hierarchyrelation
                    GROUP BY id
                ) AS activeLayer
                    ON h.id = activeLayer.id AND h.contentstreamlayer = activeLayer.contentstreamlayer
            )
            SQL,
            $hierarchyRelationStatement->toSql()
        );
    }
}
