<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\Dto;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeReferences;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;

/**
 * @Flow\Proxy(false)
 */
final class NodeSubtreeSnapshot implements \JsonSerializable
{

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var NodeTypeName
     */
    private $nodeTypeName;

    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * @var NodeAggregateClassification
     */
    private $nodeAggregateClassification;

    /**
     * @var SerializedPropertyValues
     */
    private $propertyValues;

    /**
     * @var NodeReferences
     */
    private $nodeReferences;

    /**
     * @var array|NodeSubtreeSnapshot[]
     */
    private $childNodes;

    public static function fromTraversableNode(TraversableNodeInterface $sourceNode): self
    {
        $childNodes = [];
        foreach ($sourceNode->findChildNodes() as $sourceChildNode) {
            $childNodes[] = self::fromTraversableNode($sourceChildNode);
        }

        return new self(
            $sourceNode->getNodeAggregateIdentifier(),
            $sourceNode->getNodeTypeName(),
            $sourceNode->getNodeName(),
            NodeAggregateClassification::fromNode($sourceNode),
            SerializedPropertyValues::fromNode($sourceNode),
            NodeReferences::fromArray([]), // TODO
            $childNodes
        );
    }

    /**
     * NodeToInsert constructor.
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param NodeName|null $nodeName
     * @param NodeAggregateClassification $nodeAggregateClassification
     * @param SerializedPropertyValues $propertyValues
     * @param NodeReferences $nodeReferences
     * @param array|NodeSubtreeSnapshot[] $childNodes
     */
    private function __construct(NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeName $nodeTypeName, ?NodeName $nodeName, NodeAggregateClassification $nodeAggregateClassification, SerializedPropertyValues $propertyValues, NodeReferences $nodeReferences, array $childNodes)
    {
        foreach ($childNodes as $childNode) {
            if (!$childNode instanceof NodeSubtreeSnapshot) {
                throw new \InvalidArgumentException('an element in $childNodes was not of type NodeSubtreeSnapshot, but ' . get_class($childNode));
            }
        }

        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeName = $nodeName;
        $this->nodeAggregateClassification = $nodeAggregateClassification;
        $this->propertyValues = $propertyValues;
        $this->nodeReferences = $nodeReferences;
        $this->childNodes = $childNodes;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return NodeTypeName
     */
    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    /**
     * @return NodeName
     */
    public function getNodeName(): ?NodeName
    {
        return $this->nodeName;
    }

    /**
     * @return NodeAggregateClassification
     */
    public function getNodeAggregateClassification(): NodeAggregateClassification
    {
        return $this->nodeAggregateClassification;
    }

    /**
     * @return SerializedPropertyValues
     */
    public function getPropertyValues(): SerializedPropertyValues
    {
        return $this->propertyValues;
    }

    /**
     * @return NodeReferences
     */
    public function getNodeReferences(): NodeReferences
    {
        return $this->nodeReferences;
    }

    /**
     * @return array|NodeSubtreeSnapshot[]
     */
    public function getChildNodesToInsert()
    {
        return $this->childNodes;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
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

    public static function fromArray(array $array): self
    {
        $childNodes = [];
        foreach ($array['childNodes'] as $childNode) {
            $childNodes[] = self::fromArray($childNode);
        }

        return new static(
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeTypeName::fromString($array['nodeTypeName']),
            NodeName::fromString($array['nodeName']),
            NodeAggregateClassification::fromString($array['nodeAggregateClassification']),
            SerializedPropertyValues::fromArray($array['propertyValues']),
            NodeReferences::fromArray($array['nodeReferences']),
            $childNodes
        );
    }
}
