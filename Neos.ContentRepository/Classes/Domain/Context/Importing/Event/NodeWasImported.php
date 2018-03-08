<?php
namespace Neos\ContentRepository\Domain\Context\Importing\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
use Neos\ContentRepository\Domain\ValueObject\ImportingSessionIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcing\Event\EventInterface;

final class NodeWasImported implements EventInterface, CopyableAcrossContentStreamsInterface
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

    /**
     * NodeWasImported constructor.
     * @param ImportingSessionIdentifier $importingSessionIdentifier
     * @param NodeAggregateIdentifier $parentNodeIdentifier
     * @param NodeAggregateIdentifier $nodeIdentifier
     * @param NodeName $nodeName
     * @param NodeTypeName $nodeTypeName
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param PropertyValues $propertyValues
     */
    public function __construct(
        ImportingSessionIdentifier $importingSessionIdentifier,
        NodeAggregateIdentifier $parentNodeIdentifier,
        NodeAggregateIdentifier $nodeIdentifier,
        NodeName $nodeName,
        NodeTypeName $nodeTypeName,
        DimensionSpacePoint $dimensionSpacePoint,
        PropertyValues $propertyValues
    ) {
        $this->importingSessionIdentifier = $importingSessionIdentifier;
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeName = $nodeName;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->propertyValues = $propertyValues;
    }

    /**
     * @return ImportingSessionIdentifier
     */
    public function getImportingSessionIdentifier(): ImportingSessionIdentifier
    {
        return $this->importingSessionIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getParentNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->parentNodeIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return NodeName
     */
    public function getNodeName(): NodeName
    {
        return $this->nodeName;
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
     * @return PropertyValues
     */
    public function getPropertyValues(): PropertyValues
    {
        return $this->propertyValues;
    }

    /**
     * @param ContentStreamIdentifier $targetContentStream
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        // nothing to copy here
    }
}
