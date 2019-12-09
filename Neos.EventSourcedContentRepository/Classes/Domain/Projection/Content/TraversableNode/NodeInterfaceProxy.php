<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Projection\Content\PropertyCollectionInterface;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;

/**
 * This class is purely for code organization of TraversableNode.
 *
 * @internal
 */
trait NodeInterfaceProxy
{
    /**
     * @var NodeInterface
     */
    protected $node;


    public function getCacheEntryIdentifier():string
    {
        return $this->node->getCacheEntryIdentifier();
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->node->getContentStreamIdentifier();
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->node->getNodeAggregateIdentifier();
    }

    public function getNodeTypeName(): NodeTypeName
    {
        return $this->node->getNodeTypeName();
    }

    public function getNodeType(): NodeType
    {
        return $this->node->getNodeType();
    }

    public function getNodeName(): ?NodeName
    {
        return $this->node->getNodeName();
    }

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->node->getOriginDimensionSpacePoint();
    }

    public function getProperties(): PropertyCollectionInterface
    {
        return $this->node->getProperties();
    }

    public function hasProperty($propertyName): bool
    {
        return $this->node->hasProperty($propertyName);
    }

    public function getProperty($propertyName)
    {
        return $this->node->getProperty($propertyName);
    }

    public function isRoot(): bool
    {
        return $this->node->isRoot();
    }

    public function isTethered(): bool
    {
        return $this->node->isTethered();
    }
}
