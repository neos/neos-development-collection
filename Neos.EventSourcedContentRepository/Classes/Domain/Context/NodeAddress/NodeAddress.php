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
 */
final class NodeAddress
{
    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    protected $dimensionSpacePoint;

    /**
     * @var NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var WorkspaceName
     */
    protected $workspaceName;

    /**
     * NodeAddress constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param WorkspaceName $workspaceName
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint, NodeAggregateIdentifier $nodeAggregateIdentifier, ?WorkspaceName $workspaceName)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->workspaceName = $workspaceName;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeAddress
     */
    public function withNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAddress
    {
        return new NodeAddress($this->contentStreamIdentifier, $this->dimensionSpacePoint, $nodeAggregateIdentifier, $this->workspaceName);
    }

    /**
     * @return WorkspaceName
     */
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

    public function __toString()
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
