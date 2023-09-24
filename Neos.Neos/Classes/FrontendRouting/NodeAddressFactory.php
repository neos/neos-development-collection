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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

/**
 * @api
 */
#[Flow\Scope("singleton")]
class NodeAddressFactory
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    /**
     * @deprecated will be removed before Neos 9.0
     */
    public static function create(): self
    {
        return new static();
    }

    public function createFromContentRepositoryIdAndContentStreamIdAndDimensionSpacePointAndNodeAggregateId(
        ContentRepositoryId $contentRepositoryId,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): NodeAddress {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId(
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
            $contentRepositoryId,
            $contentStreamId,
            $dimensionSpacePoint,
            $nodeAggregateId,
            $workspace->workspaceName,
        );
    }

    public function createFromNode(Node $node): NodeAddress
    {
        return $this->createFromContentRepositoryIdAndContentStreamIdAndDimensionSpacePointAndNodeAggregateId(
            $node->subgraphIdentity->contentRepositoryId,
            $node->subgraphIdentity->contentStreamId,
            $node->subgraphIdentity->dimensionSpacePoint,
            $node->nodeAggregateId,
        );
    }

    public function createFromUriString(string $serializedNodeAddress): NodeAddress
    {
        // the reverse method is {@link NodeAddress::serializeForUri} - ensure to adjust it
        // when changing the serialization here

        list($contentRepositoryIdSerialized, $workspaceNameSerialized, $dimensionSpacePointSerialized, $nodeAggregateIdSerialized)
            = explode('__', $serializedNodeAddress);
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdSerialized);
        $workspaceName = WorkspaceName::fromString($workspaceNameSerialized);
        $dimensionSpacePoint = DimensionSpacePoint::fromUriRepresentation($dimensionSpacePointSerialized);
        $nodeAggregateId = NodeAggregateId::fromString($nodeAggregateIdSerialized);

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentStreamId = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName)
            ?->currentContentStreamId;
        if (is_null($contentStreamId)) {
            throw new \InvalidArgumentException(
                'Could not resolve content stream identifier for node address ' . $serializedNodeAddress,
                1645363784
            );
        }

        return new NodeAddress(
            $contentRepositoryId,
            $contentStreamId,
            $dimensionSpacePoint,
            $nodeAggregateId,
            $workspaceName
        );
    }
}
