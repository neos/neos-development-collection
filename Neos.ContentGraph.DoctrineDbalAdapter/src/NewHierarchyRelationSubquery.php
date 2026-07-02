<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Flowpack\QueryObjectBuilder\MySQL\Builder\FromExp;
use Flowpack\QueryObjectBuilder\MySQL\Builder\SqlBuilder;
use Flowpack\QueryObjectBuilder\MySQL\Q;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Dbal\Query\SqlWhereConditionInterface;

final readonly class NewHierarchyRelationSubquery implements FromExp
{
    private function __construct(
        private NewContentGraphTableNames $tableNames,
        private ContentStreamLayers $contentStreamLayers,
        private DimensionSpacePointSet $dimensionSpacePoints,
        private NodeRelationAnchorPoint|NodeAggregateIdCondition|ReferenceDestinationNodeAggregateIdCondition|null $childNodeAnchor,
        private NodeRelationAnchorPoint|NodeAggregateIdCondition|null $parentNodeAnchor,
        private SqlWhereConditionInterface|null $whereCondition,
        private SqlWhereConditionInterface|null $possibleWhereCondition,
    ) {
    }

    public static function create(NewContentGraphTableNames $tableNames, ContentStreamLayers $contentStreamLayers): self
    {
        return new self(
            $tableNames,
            $contentStreamLayers,
            DimensionSpacePointSet::fromArray([]),
            null,
            null,
            null,
            null,
        );
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: DimensionSpacePointSet::fromArray([$dimensionSpacePoint]),
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    public function withDimensionSpacePoints(DimensionSpacePointSet $dimensionSpacePoints): self
    {
        if ($dimensionSpacePoints->isEmpty()) {
            throw new \InvalidArgumentException('Dimension space points to filter must not be empty', 1781553616);
        }
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    public function withChildNodeRelationAnchor(NodeRelationAnchorPoint $childNodeRelationAnchorPoint): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $childNodeRelationAnchorPoint,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    /**
     * Performant way to exclude additional hierarchy relations.
     *
     * As with a bloom filter, false positive matches are possible, but false negatives are not.
     * The matching hierarchy relations still must be filtered again.
     *
     * See documentation: "Optimisation: Pre-filtering"
     *
     */
    public function withPossibleChildNodeAggregateId(NodeAggregateIdCondition|ReferenceDestinationNodeAggregateIdCondition $possibleChildNodeAggregateIdCondition): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $possibleChildNodeAggregateIdCondition,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    public function withParentNodeRelationAnchor(NodeRelationAnchorPoint $parentNodeRelationAnchorPoint): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $parentNodeRelationAnchorPoint,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    /**
     * Performant way to exclude additional hierarchy relations.
     *
     * As with a bloom filter, false positive matches are possible, but false negatives are not.
     * The matching hierarchy relations still must be filtered again.
     *
     * See documentation: "Optimisation: Pre-filtering"
     *
     */
    public function withPossibleParentNodeAggregateId(NodeAggregateIdCondition $possibleParentNodeAggregateIdCondition): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $possibleParentNodeAggregateIdCondition,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    public function withWhereCondition(SqlWhereConditionInterface $whereCondition): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereCondition: $whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    /**
     * Performant way to exclude additional hierarchy relations.
     *
     * As with a bloom filter, false positive matches are possible, but false negatives are not.
     * The matching hierarchy relations still must be filtered again.
     *
     * See documentation: "Optimisation: Pre-filtering"
     *
     */
    public function withPossibleWhereCondition(SqlWhereConditionInterface $possibleWhereCondition): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $possibleWhereCondition,
        );
    }

    public function writeSql(SqlBuilder $sb): void
    {
        $q = Q::select(Q::n('h.*'))
            ->from($this->tableNames->hierarchyRelation())->as('h')
            ->where(Q::n('h.contentstreamlayer')->in(Q::args(...$this->contentStreamLayers->toIntArray())))
            ->where(Q::not(Q::exists(
                Q::select(Q::n('1'))
                    ->from($this->tableNames->hierarchyRelation())->as('hWin')
                    ->where(Q::n('hWin.id')->eq(Q::n('h.id')))
                    ->where(Q::n('hWin.contentstreamlayer')->in(Q::args(...$this->contentStreamLayers->toIntArray())))
                    ->where(Q::n('hWin.contentstreamlayer')->gt(Q::n('h.contentstreamlayer'))))
            ));

        $q = match (true) {
            $this->dimensionSpacePoints->isEmpty() => $q,
            $this->dimensionSpacePoints->count() === 1 => $q->where(Q::n('h.dimensionspacepointhash')->eq(Q::arg($this->dimensionSpacePoints->getPointHashes()[0]))),
            default => $q->where(Q::n('h.dimensionspacepointhash')->in(Q::args(...$this->dimensionSpacePoints->getPointHashes()))),
        };

        $q->writeSql($sb);
    }
}
