<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\Dto;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeReferences;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;

/**
 * @Flow\Proxy(false)
 */
final class NodeToInsert implements \JsonSerializable
{

    /**
     * The to-be-inserted NodeAggregateIdentifier
     *
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * Name of the new node's type
     *
     * @var NodeTypeName
     */
    private $nodeTypeName;

    /**
     * The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     *
     * @var NodeName
     */
    private $nodeName;

    /**
     * The node aggregate's classification
     *
     * @var NodeAggregateClassification
     */
    private $nodeAggregateClassification;

    /**
     * The node's initial property values. Will be merged over the node type's default property values
     *
     * @var PropertyValues
     */
    private $propertyValues;

    /**
     * @var NodeReferences
     */
    private $nodeReferences;

    /**
     * @var array|NodeToInsert[]
     */
    private $childNodesToInsert;

    public static function fromTraversableNode(TraversableNodeInterface $sourceNode): self
    {
        // Here, we create *new* NodeAggregateIdentifiers! -- and for top level we need to create NEW NodeNames.
        // TODO: WHERE??
        $newNodeAggregateIdentifier = NodeAggregateIdentifier::create();

        $childNodes = [];
        foreach ($sourceNode->findChildNodes() as $sourceChildNode) {
            $childNodes[] = self::fromTraversableNodeWithNodeName($sourceChildNode, $sourceChildNode->getNodeName());
        }

        return new self(
            $newNodeAggregateIdentifier,
            $sourceNode->getNodeTypeName(),
            $sourceNode->getNodeName(),
            NodeAggregateClassification::fromNode($sourceNode),
            PropertyValues::fromNode($sourceNode),
            NodeReferences::fromArray([]), // TODO
            $childNodes
        );
    }

    private static function fromTraversableNodeWithNodeName(TraversableNodeInterface $sourceNode, ?NodeName $nodeName): self
    {

    }

    /**
     * NodeToInsert constructor.
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param NodeName $nodeName
     * @param NodeAggregateClassification $nodeAggregateClassification
     * @param PropertyValues $propertyValues
     * @param NodeReferences $nodeReferences
     * @param array|NodeToInsert[] $childNodesToInsert
     */
    private function __construct(NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeName $nodeTypeName, ?NodeName $nodeName, NodeAggregateClassification $nodeAggregateClassification, PropertyValues $propertyValues, NodeReferences $nodeReferences, array $childNodesToInsert)
    {
        foreach ($childNodesToInsert as $childNodeToInsert) {
            if (!$childNodeToInsert instanceof NodeToInsert) {
                throw new \InvalidArgumentException('an element in $childNodesToInsert was not of type NodeToInsert, but ' . get_class($childNodeToInsert));
            }
        }

        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeName = $nodeName;
        $this->nodeAggregateClassification = $nodeAggregateClassification;
        $this->propertyValues = $propertyValues;
        $this->nodeReferences = $nodeReferences;
        $this->childNodesToInsert = $childNodesToInsert;
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
     * @return PropertyValues
     */
    public function getPropertyValues(): PropertyValues
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
     * @return array|NodeToInsert[]
     */
    public function getChildNodesToInsert()
    {
        return $this->childNodesToInsert;
    }

    public function withNodeName(?NodeName $nodeName): self
    {
        $copy = clone $this;
        $copy->nodeName = null;
        return $copy;
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
            'childNodesToInsert' => $this->childNodesToInsert,
        ];
    }

    public static function fromArray(array $array): self
    {
        $childNodesToInsert = [];
        foreach ($array['childNodesToInsert'] as $arrayChildNodeToInsert) {
            $childNodesToInsert[] = self::fromArray($arrayChildNodeToInsert);
        }

        return new static(
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeTypeName::fromString($array['nodeTypeName']),
            NodeName::fromString($array['nodeName']),
            NodeAggregateClassification::fromString($array['nodeAggregateClassification']),
            PropertyValues::fromArray($array['propertyValues']),
            NodeReferences::fromArray($array['nodeReferences']),
            $childNodesToInsert
        );
    }
}
