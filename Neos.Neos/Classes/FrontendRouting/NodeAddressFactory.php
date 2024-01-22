<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @api
 */
class NodeAddressFactory
{
    private function __construct(
        private readonly ContentRepository $contentRepository
    ) {
    }

    public static function create(ContentRepository $contentRepository): self
    {
        return new self($contentRepository);
    }

    public function createFromContentStreamIdAndDimensionSpacePointAndNodeAggregateId(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): NodeAddress {
        $workspace = $this->contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId(
            $contentStreamId
        );
        if ($workspace === null) {
            throw new \RuntimeException(
                'Cannot build a NodeAddress for traversable node of aggregate ' . $nodeAggregateId->value
                . ', because the content stream ' . $contentStreamId->value
                . ' is not assigned to a workspace.'
            );
        }
        return new NodeAddress(
            $contentStreamId,
            $dimensionSpacePoint,
            $nodeAggregateId,
            $workspace->workspaceName,
        );
    }

    public function createFromNode(Node $node): NodeAddress
    {
        return $this->createFromContentStreamIdAndDimensionSpacePointAndNodeAggregateId(
            $node->subgraphIdentity->contentStreamId,
            $node->subgraphIdentity->dimensionSpacePoint,
            $node->nodeAggregateId,
        );
    }

    public function createFromUriString(string $serializedNodeAddress): NodeAddress
    {
        // the reverse method is {@link NodeAddress::serializeForUri} - ensure to adjust it
        // when changing the serialization here

        list($workspaceNameSerialized, $dimensionSpacePointSerialized, $nodeAggregateIdSerialized)
            = explode('__', $serializedNodeAddress);
        $workspaceName = WorkspaceName::fromString($workspaceNameSerialized);
        $dimensionSpacePoint = DimensionSpacePoint::fromUriRepresentation($dimensionSpacePointSerialized);
        $nodeAggregateId = NodeAggregateId::fromString($nodeAggregateIdSerialized);

        $contentStreamId = $this->contentRepository->getWorkspaceFinder()->findOneByName($workspaceName)
            ?->currentContentStreamId;
        if (is_null($contentStreamId)) {
            throw new \InvalidArgumentException(
                'Could not resolve content stream identifier for node address ' . $serializedNodeAddress,
                1645363784
            );
        }

        return new NodeAddress(
            $contentStreamId,
            $dimensionSpacePoint,
            $nodeAggregateId,
            $workspaceName
        );
    }
}
