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

namespace Neos\ContentRepository\SharedModel;

use Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use function Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\sprintf;

/**
 * A persistent, external "address" of a node; used to link to it.
 *
 * Describes the intention of the user making the current request:
 * Show me
 *  node $nodeAggregateIdentifier
 *  in dimensions $dimensionSpacePoint
 *  in contentStreamIdentifier $contentStreamIdentifier
 *
 * It is used in Neos Routing to build a URI to a node.
 */
#[Flow\Proxy(false)]
final class NodeAddress
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly DimensionSpacePoint $dimensionSpacePoint,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly ?WorkspaceName $workspaceName
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            DimensionSpacePoint::fromArray($array['dimensionSpacePoint']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            isset($array['workspaceName']) ? WorkspaceName::fromString($array['workspaceName']) : null
        );
    }

    public function withNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        return new self(
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            $nodeAggregateIdentifier,
            $this->workspaceName
        );
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self(
            $this->contentStreamIdentifier,
            $dimensionSpacePoint,
            $this->nodeAggregateIdentifier,
            $this->workspaceName
        );
    }

    /**
     * @throws NodeAddressCannotBeSerializedException
     */
    public function serializeForUri(): string
    {
        // the reverse method is {@link NodeAddressFactory::createFromUriString} - ensure to adjust it
        // when changing the serialization here
        if ($this->workspaceName === null) {
            throw NodeAddressCannotBeSerializedException::becauseNoWorkspaceNameWasResolved($this);
        }
        return $this->workspaceName->name
            . '__' . $this->dimensionSpacePoint->serializeForUri()
            . '__' . $this->nodeAggregateIdentifier->jsonSerialize();
    }

    public function isInLiveWorkspace(): bool
    {
        return $this->workspaceName?->isLive() ?: false;
    }

    public function __toString(): string
    {
        return sprintf(
            'NodeAddress[contentStream=%s, dimensionSpacePoint=%s, nodeAggregateIdentifier=%s, workspaceName=%s]',
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            $this->nodeAggregateIdentifier,
            $this->workspaceName
        );
    }
}
