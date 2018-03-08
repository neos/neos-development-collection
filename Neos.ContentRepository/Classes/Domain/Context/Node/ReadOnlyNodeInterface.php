<?php
namespace Neos\ContentRepository\Domain\Context\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
use Neos\ContentRepository\Domain\Projection\Content\NodePropertyCollection;
use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcing\Event\Decorator\EventWithMetadata;
use Neos\EventSourcing\Event\EventInterface;
use Neos\EventSourcing\Event\EventPublisher;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\TypeConverter\EventToArrayConverter;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;

/**
 * The "new" Event-Sourced NodeInterface. Supersedes the old Neos\ContentRepository\Domain\Model\NodeInterface.
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
