<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeDuplication\Dto;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * Implementation detail of {@see CopyNodesRecursively}
 *
 * @internal You'll never create this class yourself; see {@see CopyNodesRecursively::createFromSubgraphAndStartNode()}
 */
final readonly class NodeSubtreeSnapshot implements \JsonSerializable
{
    /**
     * @param NodeSubtreeSnapshot[] $childNodes
     */
    private function __construct(
        public NodeAggregateId $nodeAggregateId,
        public NodeTypeName $nodeTypeName,
        public ?NodeName $nodeName,
        public NodeAggregateClassification $nodeAggregateClassification,
        public SerializedPropertyValues $propertyValues,
        public NodeReferencesSnapshot $nodeReferences,
        public array $childNodes
    ) {
        foreach ($childNodes as $childNode) {
            if (!$childNode instanceof NodeSubtreeSnapshot) {
                throw new \InvalidArgumentException(
                    'an element in $childNodes was not of type NodeSubtreeSnapshot, but ' . get_class($childNode)
                );
            }
        }
    }

    public static function fromSubgraphAndStartNode(ContentSubgraphInterface $subgraph, Node $sourceNode): self
    {
        $childNodes = [];
        foreach (
            $subgraph->findChildNodes($sourceNode->aggregateId, FindChildNodesFilter::create()) as $sourceChildNode
        ) {
            $childNodes[] = self::fromSubgraphAndStartNode($subgraph, $sourceChildNode);
        }
        $properties = $sourceNode->properties;

        return new self(
            $sourceNode->aggregateId,
            $sourceNode->nodeTypeName,
            $sourceNode->name,
            $sourceNode->classification,
            $properties->serialized(),
            NodeReferencesSnapshot::fromReferences(
                $subgraph->findReferences($sourceNode->aggregateId, FindReferencesFilter::create())
            ),
            $childNodes
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'nodeAggregateId' => $this->nodeAggregateId,
            'nodeTypeName' => $this->nodeTypeName,
            'nodeName' => $this->nodeName,
            'nodeAggregateClassification' => $this->nodeAggregateClassification,
            'propertyValues' => $this->propertyValues,
            'nodeReferences' => $this->nodeReferences,
            'childNodes' => $this->childNodes,
        ];
    }

    public function walk(\Closure $forEachElementFn): void
    {
        $forEachElementFn($this);
        foreach ($this->childNodes as $childNode) {
            $childNode->walk($forEachElementFn);
        }
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        $childNodes = [];
        foreach ($array['childNodes'] as $childNode) {
            $childNodes[] = self::fromArray($childNode);
        }

        return new self(
            NodeAggregateId::fromString($array['nodeAggregateId']),
            NodeTypeName::fromString($array['nodeTypeName']),
            isset($array['nodeName']) ? NodeName::fromString($array['nodeName']) : null,
            NodeAggregateClassification::from($array['nodeAggregateClassification']),
            SerializedPropertyValues::fromArray($array['propertyValues']),
            NodeReferencesSnapshot::fromArray($array['nodeReferences']),
            $childNodes
        );
    }
}
