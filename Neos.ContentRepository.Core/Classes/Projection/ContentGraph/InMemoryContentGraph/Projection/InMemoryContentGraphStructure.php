<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The actual in-memory graph structure, connecting @see InMemoryNodeRecord objects
 *
 * @internal
 */
final class InMemoryContentGraphStructure
{
    private static ?self $instance = null;

    /**
     * @param array<string,array<string,array<string,InMemoryNodeRecord>>> $nodes
     * indexed by content stream ID, node aggregate ID and origin dimension space point hash
     * @param array<string,array<string,InMemoryNodeRecord>> $rootNodes
     * indexed by content stream ID and (root) node type name
     * @param array<string,InMemoryReferenceHyperrelation> $references
     * indexed by content stream ID
     */
    private function __construct(
        public array $nodes = [],
        public array $rootNodes = [],
        public array $references = [],
        public int $totalNodeCount = 0,
    ) {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function reset(): void
    {
        $this->nodes = [];
        $this->rootNodes = [];
        $this->references = [];
        $this->totalNodeCount = 0;
    }

    public function initializeContentStream(ContentStreamId $contentStreamId): void
    {
        $this->nodes[$contentStreamId->value] = [];
        $this->rootNodes[$contentStreamId->value] = [];
        $this->references[$contentStreamId->value] = new InMemoryReferenceHyperrelation();
    }

    public function removeContentStream(ContentStreamId $contentStreamId): void
    {
        unset($this->nodes[$contentStreamId->value]);
        unset($this->rootNodes[$contentStreamId->value]);
        unset($this->references[$contentStreamId->value]);
    }

    public function addNodeRecord(InMemoryNodeRecord $nodeRecord, ContentStreamId $contentStreamId): void
    {
        $this->nodes[$contentStreamId->value][$nodeRecord->nodeAggregateId->value][$nodeRecord->originDimensionSpacePoint->hash] = $nodeRecord;
        $this->totalNodeCount++;
    }
}
