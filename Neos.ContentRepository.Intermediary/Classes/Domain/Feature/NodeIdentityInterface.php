<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain\Feature;

/*
 * This file is part of the Neos.ContentRepository.Api package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;

/**
 * The feature interface declaring node identity
 */
interface NodeIdentityInterface extends CacheAwareInterface
{
    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier;

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier;

    /**
     * Returns the DimensionSpacePoint the node was *requested in*, i.e. one of the DimensionSpacePoints
     * this node is visible in. If you need the DimensionSpacePoint where the node is actually at home,
     * see getOriginDimensionSpacePoint()
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint;

    /**
     * returns the DimensionSpacePoint the node is at home in. Usually needed to address a Node in a NodeAggregate
     * in order to update it.
     */
    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint;

    public function getAddress(): NodeAddress;

    /**
     * Compare whether two nodes are equal
     */
    public function equals(NodeIdentityInterface $other): bool;
}
