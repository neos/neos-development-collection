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

namespace Neos\ContentRepository\SharedModel;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\Service\NodePaths;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;

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

    public function createFromNode(NodeInterface $node): NodeAddress
    {
        $workspace = $this->contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamIdentifier(
            $node->getSubgraphIdentity()->contentStreamIdentifier
        );
        if ($workspace === null) {
            throw new \RuntimeException(
                'Cannot build a NodeAddress for traversable node of aggregate ' . $node->getNodeAggregateIdentifier()
                . ', because the content stream ' . $node->getSubgraphIdentity()->contentStreamIdentifier
                . ' is not assigned to a workspace.'
            );
        }
        return new NodeAddress(
            $node->getSubgraphIdentity()->contentStreamIdentifier,
            $node->getSubgraphIdentity()->dimensionSpacePoint,
            $node->getNodeAggregateIdentifier(),
            $workspace->getWorkspaceName()
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
            ?->getCurrentContentStreamIdentifier();
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

    /**
     * @param string $contextPath
     * @return NodeAddress
     * @deprecated make use of createFromUriString instead
     */
    public function createFromContextPath(string $contextPath): NodeAddress
    {
        $pathValues = NodePaths::explodeContextPath($contextPath);
        $workspace = $this->contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($pathValues['workspaceName']));
        if (is_null($workspace)) {
            throw new \InvalidArgumentException('No workspace exists for context path ' . $contextPath, 1645363699);
        }
        $contentStreamIdentifier = $workspace->getCurrentContentStreamIdentifier();
        $dimensionSpacePoint = DimensionSpacePoint::fromLegacyDimensionArray($pathValues['dimensions']);
        $nodePath = NodePath::fromString(\mb_strpos($pathValues['nodePath'], '/sites') === 0
            ? \mb_substr($pathValues['nodePath'], 6)
            : $pathValues['nodePath']);

        $subgraph = $this->contentRepository->getContentGraph()->getSubgraphByIdentifier(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $node = $subgraph->findNodeByPath(
            $nodePath,
            $this->contentRepository->getContentGraph()->findRootNodeAggregateByType(
                $contentStreamIdentifier,
                NodeTypeName::fromString('Neos.Neos:Sites')
            )->getIdentifier()
        );
        if (is_null($node)) {
            throw new \InvalidArgumentException('No node exists on context path ' . $contextPath, 1645363666);
        }

        return new NodeAddress(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            $node->getNodeAggregateIdentifier(),
            $workspace->getWorkspaceName()
        );
    }
}
