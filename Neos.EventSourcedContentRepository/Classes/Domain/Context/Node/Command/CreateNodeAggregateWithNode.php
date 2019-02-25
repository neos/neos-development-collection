<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;

/**
 * CreateNodeAggregateWithNode command
 *
 * Creates a new node aggregate with a new node with the given `nodeAggregateIdentifier` and `nodeIdentifier`.
 * The node will be appended as child node of the given `parentNodeIdentifier` which must be visible in the given
 * `dimensionSpacePoint`.
 */
final class CreateNodeAggregateWithNode implements \JsonSerializable
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var NodeTypeName
     */
    private $nodeTypeName;

    /**
     * Location of the new node in the dimension space
     *
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $parentNodeIdentifier;

    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * CreateNodeAggregateWithNode constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier New node aggregate identifier
     * @param NodeTypeName $nodeTypeName
     * @param DimensionSpacePoint $dimensionSpacePoint The dimension space point of the node, will be used to calculate a set of dimension points from the configured generalizations
     * @param NodeIdentifier $nodeIdentifier New node identifier
     * @param NodeIdentifier $parentNodeIdentifier Parent node of the created node
     * @param NodeName $nodeName
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeName $nodeTypeName, DimensionSpacePoint $dimensionSpacePoint, NodeIdentifier $nodeIdentifier, NodeIdentifier $parentNodeIdentifier, NodeName $nodeName)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeName = $nodeName;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeTypeName::fromString($array['nodeTypeName']),
            new DimensionSpacePoint($array['dimensionSpacePoint']),
            NodeIdentifier::fromString($array['nodeIdentifier']),
            NodeIdentifier::fromString($array['parentNodeIdentifier']),
            NodeName::fromString($array['nodeName'])
        );
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
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
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getParentNodeIdentifier(): NodeIdentifier
    {
        return $this->parentNodeIdentifier;
    }

    /**
     * @return NodeName
     */
    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'nodeTypeName' => $this->nodeTypeName,
            'dimensionSpacePoint' => $this->dimensionSpacePoint,
            'nodeIdentifier' => $this->nodeIdentifier,
            'parentNodeIdentifier' => $this->parentNodeIdentifier,
            'nodeName' => $this->nodeName,
        ];
    }
}
