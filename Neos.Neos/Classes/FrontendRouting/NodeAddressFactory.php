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
use Neos\Neos\FrontendRouting\NodeAddress as LegacyNodeAddress;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @deprecated will be removed before Final 9.0
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

    public function createFromNode(Node $node): LegacyNodeAddress
    {
        return new LegacyNodeAddress(
            null,
            $node->dimensionSpacePoint,
            $node->aggregateId,
            $node->workspaceName,
        );
    }

    public function createCoreNodeAddressFromLegacyUriString(string $serializedNodeAddress): NodeAddress
    {
        $legacy = $this->createFromUriString($serializedNodeAddress);

        return NodeAddress::create(
            $this->contentRepository->id,
            $legacy->workspaceName,
            $legacy->dimensionSpacePoint,
            $legacy->nodeAggregateId
        );
    }

    public function createFromUriString(string $serializedNodeAddress): LegacyNodeAddress
    {
        // the reverse method is {@link NodeAddress::serializeForUri} - ensure to adjust it
        // when changing the serialization here

        list($workspaceNameSerialized, $dimensionSpacePointSerialized, $nodeAggregateIdSerialized)
            = explode('__', $serializedNodeAddress);
        $workspaceName = WorkspaceName::fromString($workspaceNameSerialized);
        $dimensionSpacePoint = DimensionSpacePoint::fromArray(json_decode(base64_decode($dimensionSpacePointSerialized), true));
        $nodeAggregateId = NodeAggregateId::fromString($nodeAggregateIdSerialized);

        return new LegacyNodeAddress(
            null,
            $dimensionSpacePoint,
            $nodeAggregateId,
            $workspaceName
        );
    }
}
