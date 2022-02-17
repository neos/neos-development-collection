<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAddress;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;

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
 *
 * @Flow\Proxy(false)
 */
final class NodeAddress
{
    /**
     * @var ContentStreamIdentifier
     */
    protected ContentStreamIdentifier $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    protected DimensionSpacePoint $dimensionSpacePoint;

    /**
     * @var NodeAggregateIdentifier
     */
    protected NodeAggregateIdentifier $nodeAggregateIdentifier;

    /**
     * @var WorkspaceName|null
     */
    protected ?WorkspaceName $workspaceName;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?WorkspaceName $workspaceName
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->workspaceName = $workspaceName;
    }

    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            DimensionSpacePoint::instance($array['dimensionSpacePoint']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            isset($array['workspaceName']) ? WorkspaceName::fromString($array['workspaceName']) : null
        );
    }

    public function withNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        return new self($this->contentStreamIdentifier, $this->dimensionSpacePoint, $nodeAggregateIdentifier, $this->workspaceName);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self($this->contentStreamIdentifier, $dimensionSpacePoint, $this->nodeAggregateIdentifier, $this->workspaceName);
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getWorkspaceName(): ?WorkspaceName
    {
        return $this->workspaceName;
    }

    public function serializeForUri(): string
    {
        // the reverse method is {@link NodeAddressFactory::createFromUriString} - ensure to adjust it
        // when changing the serialization here
        if ($this->workspaceName === null) {
            throw new \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\Exception\NodeAddressCannotBeSerializedException('The node Address ' . $this->__toString() . ' cannot be serialized because no workspace name was resolved.', 1531637028);
        }
        return $this->workspaceName->jsonSerialize() . '__' . $this->dimensionSpacePoint->serializeForUri() . '__' . $this->nodeAggregateIdentifier->jsonSerialize();
    }

    public function isInLiveWorkspace(): bool
    {
        return $this->workspaceName != null && $this->workspaceName->isLive();
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
