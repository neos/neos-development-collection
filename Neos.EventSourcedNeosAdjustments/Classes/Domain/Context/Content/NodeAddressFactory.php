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

use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
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

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

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

    /**
     * @param string $contextPath
     * @return NodeAddress
     * @deprecated make use of createFromUriString instead
     */
    public function createFromContextPath(string $contextPath): NodeAddress
    {
        $pathValues = NodePaths::explodeContextPath($contextPath);
        $workspace = $this->workspaceFinder->findOneByName(new WorkspaceName($pathValues['workspaceName']));
        $contentStreamIdentifier = $workspace->getCurrentContentStreamIdentifier();
        $dimensionSpacePoint = DimensionSpacePoint::fromLegacyDimensionArray($pathValues['dimensions']);
        $nodePath = \mb_strpos($pathValues['nodePath'], '/sites') === 0 ? \mb_substr($pathValues['nodePath'], 6) : $pathValues['nodePath'];

        $subgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $dimensionSpacePoint);
        $node = $subgraph->findNodeByPath($nodePath, $this->contentGraph->findRootNodeByType(new NodeTypeName('Neos.Neos:Sites'))->getNodeIdentifier());

        return new NodeAddress($contentStreamIdentifier, $dimensionSpacePoint, $node->getNodeAggregateIdentifier(), $workspace->getWorkspaceName());
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
