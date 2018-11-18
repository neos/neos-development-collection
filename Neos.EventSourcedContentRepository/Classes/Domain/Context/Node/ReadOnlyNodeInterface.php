<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodePropertyCollection;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;

/**
 * The "new" Event-Sourced NodeInterface. Supersedes the old Neos\EventSourcedContentRepository\Domain\Model\NodeInterface.
 */
interface ReadOnlyNodeInterface
{
    public function getContentStreamIdentifier(): ContentStreamIdentifier;

    public function getNodeIdentifier(): NodeIdentifier;

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier;

    public function getNodeTypeName(): NodeTypeName;

    public function getNodeName(): NodeName;

    public function getDimensionSpacePoint(): DimensionSpacePoint;

    /**
     * Returns all properties of this node.
     *
     * If the node has a content object attached, the properties will be fetched
     * there.
     *
     * @return NodePropertyCollection Property values, indexed by their name
     * @api
     */
    public function getProperties(): NodePropertyCollection;


    /**
     * Returns the specified property.
     *
     * If the node has a content object attached, the property will be fetched
     * there if it is gettable.
     *
     * @param string $propertyName Name of the property
     * @return mixed value of the property
     * @throws NodeException if the node does not contain the specified property
     * @api
     */
    public function getProperty($propertyName);

    /**
     * Returns the current state of the hidden flag
     *
     * @return boolean
     * @api
     */
    public function isHidden();
}
