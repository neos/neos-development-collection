<?php
namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A node identifier and dimension space point set
 */
final class NodeIdentifierAndDimensionSpacePointSet implements \JsonSerializable
{

    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var DimensionSpacePointSet
     */
    private $dimensionSpacePointSet;

    public function __construct(NodeIdentifier $nodeIdentifier, DimensionSpacePointSet $dimensionSpacePointSet)
    {
        $this->nodeIdentifier = $nodeIdentifier;
        $this->dimensionSpacePointSet = $dimensionSpacePointSet;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getDimensionSpacePointSet(): DimensionSpacePointSet
    {
        return $this->dimensionSpacePointSet;
    }

    public function jsonSerialize()
    {
        return [
            'nodeIdentifier' => $this->nodeIdentifier,
            'dimensionSpacePointSet' => $this->dimensionSpacePointSet
        ];
    }

    public function __toString()
    {
        return $this->nodeIdentifier . ': ' . $this->dimensionSpacePointSet;
    }
}
