<?php
namespace Neos\ContentRepository\Domain\Context\Importing\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\ImportingSessionIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValues;

final class ImportNode
{
    /**
     * @var ImportingSessionIdentifier
     */
    private $importingSessionIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $parentNodeIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * @var NodeTypeName
     */
    private $nodeTypeName;

    /**
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * @var PropertyValues
     */
    private $propertyValues;

    public function __construct(
        ImportingSessionIdentifier $importingSessionIdentifier,
        NodeAggregateIdentifier $parentNodeIdentifier,
        NodeAggregateIdentifier $nodeIdentifier,
        NodeName $nodeName,
        NodeTypeName $nodeTypeName,
        DimensionSpacePoint $dimensionValues,
        PropertyValues $propertyValues
    ) {
        $this->importingSessionIdentifier = $importingSessionIdentifier;
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeName = $nodeName;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionSpacePoint = $dimensionValues;
        $this->propertyValues = $propertyValues;
    }

    public function getImportingSessionIdentifier(): ImportingSessionIdentifier
    {
        return $this->importingSessionIdentifier;
    }

    public function getParentNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->parentNodeIdentifier;
    }

    public function getNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeIdentifier;
    }

    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }

    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getPropertyValues(): PropertyValues
    {
        return $this->propertyValues;
    }
}
