<?php

namespace Neos\EventSourcedNeosAdjustments\Domain\Context\Content;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NodeAddressFactory
{

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    public function createFromNode(NodeInterface $node): NodeAddress
    {
        $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($node->getContentStreamIdentifier());
        return new NodeAddress($node->getContentStreamIdentifier(), $node->getDimensionSpacePoint(), $node->getNodeAggregateIdentifier(), $workspace->getWorkspaceName());
    }

    public function createFromUriString(string $nodeAddressSerialized): NodeAddress
    {
        // the reverse method is {@link NodeAddress::serializeForUri} - ensure to adjust it
        // when changing the serialization here

        list($workspaceNameSerialized, $dimensionSpacePointSerialized, $nodeAggregateIdentifierSerialized) = explode('__', $nodeAddressSerialized);
        $workspaceName = new WorkspaceName($workspaceNameSerialized);
        $dimensionSpacePoint = DimensionSpacePoint::fromUriRepresentation($dimensionSpacePointSerialized);
        $nodeAggregateIdentifier = new NodeAggregateIdentifier($nodeAggregateIdentifierSerialized);

        $contentStreamIdentifier = $this->workspaceFinder->findOneByName($workspaceName)->getCurrentContentStreamIdentifier();

        return new NodeAddress($contentStreamIdentifier, $dimensionSpacePoint, $nodeAggregateIdentifier, $workspaceName);
    }

    public function adjustWithDimensionSpacePoint(NodeAddress $baseNodeAddress, DimensionSpacePoint $dimensionSpacePoint): NodeAddress
    {
        if ($dimensionSpacePoint->getHash() === $baseNodeAddress->getDimensionSpacePoint()->getHash()) {
            // optimization if dimension space point does not need adjusting
            return $baseNodeAddress;
        }

        return new NodeAddress(
            $baseNodeAddress->getContentStreamIdentifier(),
            $dimensionSpacePoint,
            $baseNodeAddress->getNodeAggregateIdentifier(),
            $baseNodeAddress->getWorkspaceName()
        );
    }

    public function adjustWithNodeAggregateIdentifier(NodeAddress $baseNodeAddress, NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAddress
    {
        if ($nodeAggregateIdentifier->jsonSerialize() === $baseNodeAddress->getNodeAggregateIdentifier()->jsonSerialize()) {
            // optimization if NodeAggregateIdentifier does not need adjusting
            return $baseNodeAddress;
        }

        return new NodeAddress(
            $baseNodeAddress->getContentStreamIdentifier(),
            $baseNodeAddress->getDimensionSpacePoint(),
            $nodeAggregateIdentifier,
            $baseNodeAddress->getWorkspaceName()
        );
    }
}
