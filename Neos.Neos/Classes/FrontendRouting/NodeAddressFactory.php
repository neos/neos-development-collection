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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\FrontendRouting\NodeAddress;

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

    public function createFromNode(Node $node): NodeAddress
    {
        $workspace = $this->contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamIdentifier(
            $node->subgraphIdentity->contentStreamIdentifier
        );
        if ($workspace === null) {
            throw new \RuntimeException(
                'Cannot build a NodeAddress for traversable node of aggregate ' . $node->nodeAggregateIdentifier
                . ', because the content stream ' . $node->subgraphIdentity->contentStreamIdentifier
                . ' is not assigned to a workspace.'
            );
        }
        return new NodeAddress(
            $node->subgraphIdentity->contentStreamIdentifier,
            $node->subgraphIdentity->dimensionSpacePoint,
            $node->nodeAggregateIdentifier,
            $workspace->workspaceName
        );
    }

    public function createFromUriString(string $serializedNodeAddress): NodeAddress
    {
        // the reverse method is {@link NodeAddress::serializeForUri} - ensure to adjust it
        // when changing the serialization here

        list($workspaceNameSerialized, $dimensionSpacePointSerialized, $nodeAggregateIdentifierSerialized)
            = explode('__', $serializedNodeAddress);
        $workspaceName = WorkspaceName::fromString($workspaceNameSerialized);
        $dimensionSpacePoint = DimensionSpacePoint::fromUriRepresentation($dimensionSpacePointSerialized);
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeAggregateIdentifierSerialized);

        $contentStreamIdentifier = $this->contentRepository->getWorkspaceFinder()->findOneByName($workspaceName)
            ?->currentContentStreamIdentifier;
        if (is_null($contentStreamIdentifier)) {
            throw new \InvalidArgumentException(
                'Could not resolve content stream identifier for node address ' . $serializedNodeAddress,
                1645363784
            );
        }

        return new NodeAddress(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            $nodeAggregateIdentifier,
            $workspaceName
        );
    }
}
