<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeDuplication\Dto;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReference;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferencesForName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\SharedModel\Exception\TetheredNodesCannotBePartiallyCopied;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

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
        public SerializedNodeReferences $nodeReferences,
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
        return self::createSnapshotRecursively($subgraph, $sourceNode, true);
    }

    private static function createSnapshotRecursively(ContentSubgraphInterface $subgraph, Node $sourceNode, bool $firstLevel): self
    {
        $childNodeSnapshots = [];
        foreach (
            $subgraph->findChildNodes($sourceNode->aggregateId, FindChildNodesFilter::create()) as $sourceChildNode
        ) {
            if ($firstLevel && $sourceNode->classification->isTethered() && $sourceChildNode->classification->isTethered()) {
                // we assume here that the child node is tethered because the grandparent specifies that.
                // this is not always fully correct and we could loosen the constraint by checking the node type schema
                throw new TetheredNodesCannotBePartiallyCopied(sprintf('Cannot copy tethered node %s because child node %s is also tethered. Only standalone tethered nodes can be copied.', $sourceNode->aggregateId->value, $sourceChildNode->aggregateId->value), 1731264887);
            }

            $childNodeSnapshots[] = self::createSnapshotRecursively($subgraph, $sourceChildNode, false);
        }

        return new self(
            $sourceNode->aggregateId,
            $sourceNode->nodeTypeName,
            $sourceNode->name,
            $firstLevel ? NodeAggregateClassification::CLASSIFICATION_REGULAR : $sourceNode->classification,
            $sourceNode->properties->serialized(),
            self::serializeProjectedReferences(
                $subgraph->findReferences($sourceNode->aggregateId, FindReferencesFilter::create())
            ),
            $childNodeSnapshots
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
            SerializedNodeReferences::fromArray($array['nodeReferences']),
            $childNodes
        );
    }

    private static function serializeProjectedReferences(References $references): SerializedNodeReferences
    {
        $serializedReferences = [];
        $serializedReferencesByName = [];
        foreach ($references as $reference) {
            if (!isset($serializedReferencesByName[$reference->name->value])) {
                $serializedReferencesByName[$reference->name->value] = [];
            }
            $serializedReferencesByName[$reference->name->value][] = SerializedNodeReference::fromTargetAndProperties($reference->node->aggregateId, $reference->properties ? $reference->properties->serialized() : SerializedPropertyValues::createEmpty());
        }

        foreach ($serializedReferencesByName as $name => $referenceObjects) {
            $serializedReferences[] = SerializedNodeReferencesForName::fromSerializedReferences(ReferenceName::fromString($name), $referenceObjects);
        }

        return SerializedNodeReferences::fromArray($serializedReferences);
    }
}
