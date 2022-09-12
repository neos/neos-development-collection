<?php

/*
 * This file is part of the Neos.ContentRepository package.
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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * A persistent, external "address" of a node; used to link to it.
 *
 * Describes the intention of the user making the current request:
 * Show me
 *  node $nodeAggregateId
 *  in dimensions $dimensionSpacePoint
 *  in contentStreamIdentifier $contentStreamIdentifier
 *
 * It is used in Neos Routing to build a URI to a node.
 *
 * @api
 */
#[Flow\Proxy(false)]
final class NodeAddress
{
    /**
     * @internal use NodeAddressFactory, if you want to create a NodeAddress
     */
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly DimensionSpacePoint $dimensionSpacePoint,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly WorkspaceName $workspaceName
    ) {
    }

    public function withNodeAggregateId(NodeAggregateId $nodeAggregateId): self
    {
        return new self(
            $this->contentStreamId,
            $this->dimensionSpacePoint,
            $nodeAggregateId,
            $this->workspaceName
        );
    }

    public function serializeForUri(): string
    {
        // the reverse method is {@link NodeAddressFactory::createFromUriString} - ensure to adjust it
        // when changing the serialization here
        return $this->workspaceName->name
            . '__' . base64_encode(json_encode($this->dimensionSpacePoint->coordinates, JSON_THROW_ON_ERROR))
            . '__' . $this->nodeAggregateId->jsonSerialize();
    }

    public function isInLiveWorkspace(): bool
    {
        return $this->workspaceName->isLive();
    }

    public function __toString(): string
    {
        return sprintf(
            'NodeAddress[contentStream=%s, dimensionSpacePoint=%s, nodeAggregateIdentifier=%s, workspaceName=%s]',
            $this->contentStreamId,
            $this->dimensionSpacePoint,
            $this->nodeAggregateId,
            $this->workspaceName
        );
    }
}
