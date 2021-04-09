<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\ContentAccess;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\SearchTerm;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

/**
 * Trait which implements delegation. Compose this into your own custom NodeAccessors. See {@see NodeAccessorInterface} for a full
 * usage description.
 */
trait DelegatingNodeAccessorTrait
{
    protected NodeAccessorInterface $nextAccessor;

    public function findByIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface
    {
        return $this->nextAccessor->findByIdentifier($nodeAggregateIdentifier);
    }

    public function findChildNodes(NodeInterface $parentNode, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): iterable
    {
        return $this->nextAccessor->findChildNodes($parentNode, $nodeTypeConstraints, $limit, $offset);
    }

    public function findReferencedNodes(NodeInterface $node, PropertyName $name = null): iterable
    {
        return $this->nextAccessor->findReferencedNodes($node, $name);
    }

    public function findReferencingNodes(NodeInterface $node, PropertyName $name = null): iterable
    {
        return $this->nextAccessor->findReferencingNodes($node, $name);
    }

    public function findParentNode(NodeInterface $childNode): ?NodeInterface
    {
        return $this->nextAccessor->findParentNode($childNode);
    }

    public function findNodeByPath(NodePath $path, NodeInterface $startingNode): ?NodeInterface
    {
        return $this->nextAccessor->findNodeByPath($path, $startingNode);
    }

    public function findChildNodeConnectedThroughEdgeName(NodeInterface $parentNode, NodeName $edgeName): ?NodeInterface
    {
        return $this->nextAccessor->findChildNodeConnectedThroughEdgeName($parentNode, $edgeName);
    }

    public function findNodePath(NodeInterface $node): NodePath
    {
        return $this->nextAccessor->findNodePath($node);
    }

    public function findSubtrees(array $entryNodes, int $maximumLevels, NodeTypeConstraints $nodeTypeConstraints): SubtreeInterface
    {
        return $this->nextAccessor->findSubtrees($entryNodes, $maximumLevels, $nodeTypeConstraints);
    }

    public function findDescendants(array $entryNodes, NodeTypeConstraints $nodeTypeConstraints, ?SearchTerm $searchTerm): iterable
    {
        return $this->nextAccessor->findDescendants($entryNodes, $nodeTypeConstraints, $searchTerm);
    }

}
