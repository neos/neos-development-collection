<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\SharedModel\Node\PropertyCollectionInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;

interface NodeInterface extends \Neos\ContentRepository\Domain\Projection\Content\NodeInterface
{
    /**
     * Returns all properties of this node. References are NOT part of this API;
     * there you need to check getReference() and getReferences().
     *
     * To read the serialized properties, call getProperties()->serialized().
     *
     * @return PropertyCollectionInterface Property values, indexed by their name
     * @api
     */
    public function getProperties(): PropertyCollectionInterface;

    /**
     * DimensionSpacePoint this node has been accessed in.
     * This is part of the node's "Read Model" identity, whis is defined by:
     * - {@see getContentStreamIdentifier}
     * - {@see getNodeAggregateIdentifier}
     * - {@see getDimensionSpacePoint} (this method)
     * - {@see getVisibilityConstraints}
     *
     * With the above information, you can fetch a Node Accessor using {@see NodeAccessorManager::accessorFor()}, or
     * (for lower-level access) a Subgraph using {@see ContentGraphInterface::getSubgraphByIdentifier()}.
     *
     * This is the DimensionSpacePoint this node has been accessed in
     * - NOT the DimensionSpacePoint where the node is "at home".
     * The DimensionSpacePoint where the node is (at home) is called the ORIGIN DimensionSpacePoint,
     * and this can be accessed using {@see getOriginDimensionSpacePoint}. If in doubt, you'll usually need this method
     * insead of the Origin DimensionSpacePoint.
     *
     * We are still a bit unsure whether this method should be part of the Node itself, or rather part of some kind of
     * "Context Accessor" or "Perspective" object.
     *
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint;

    /**
     * VisibilityConstraints of the Subgraph / NodeAccessor this node has been read from.
     * This is part of the node's "Read Model" identity, whis is defined by:
     * - {@see getContentStreamIdentifier}
     * - {@see getNodeAggregateIdentifier}
     * - {@see getDimensionSpacePoint}
     * - {@see getVisibilityConstraints} (this method)
     *
     * With the above information, you can fetch a Node Accessor using {@see NodeAccessorManager::accessorFor()}, or
     * (for lower-level access) a Subgraph using {@see ContentGraphInterface::getSubgraphByIdentifier()}.
     *
     * We are still a bit unsure whether this method should be part of the Node itself, or rather part of some kind of
     * "Context Accessor" or "Perspective" object.
     *
     * @return VisibilityConstraints
     */
    public function getVisibilityConstraints(): VisibilityConstraints;

    public function getClassification(): NodeAggregateClassification;

    public function equals(NodeInterface $other): bool;
}
