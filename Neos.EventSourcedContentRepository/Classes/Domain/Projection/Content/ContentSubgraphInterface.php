<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

// have only ONE NODE (Interface - IM CORE) (no wrapping or anything like this)
    // von mir aus kann es dafür ne konfigurierbare "low level" Factory geben? <-- unplanned extensibility -- !! das machen wir in den Projection Packages
    // this "one node" can give me serialized and object properties
// Intermediary Package auflösen?
// Heute ist aus Lese-Sicht das Konzept "Subgraph" zentral.
    // Problem 1: ne API-Methode darin hat "sehr wenig wissen" über den Startpunkt, da es nur den NAI bekommt und nicht den Node selbst
    // Problem 2: Es fehlt ggf. "Kontextwissen"
// -> ENTWEDER: wir lösen diese Probleme für Subgraph
// -> ODER: wir fügen JETZT ein explizites Konzept für dieses "Dispatching" ein -> Accessor / Traverser
    // -> API-mäßig ist der sehr nah am aktuellen Subgraph dran
// 3) noch radikalere Ansätze (Query Model)

class DispatchingAccessor implements Accessor implements FindChildNodes, FindReferencedNodes, FindByIdentifier {
    public function findChildNodes(NodeInterface $node, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array {


        // TODO: ContentStreamIdentifier, DimensionSpacePoint muss im Constructor hier rein
        // TODO: CONTEXT?? Presets?? (Jahr, Monat)
        $subAccessor = $this->findChildNodesAccessorRegistry->findMatchingAccessor($node, $this->context /* ;-) */); // Entscheidung bspw. basierend auf NodeType
        assert($subAccessor instanceof FindChildNodes);
        return $subAccessor->findChildNodes($node, $nodeTypeConstraints, ...);
    }
}

class MysqlContentSubgraph implements FindChildNodes, FindReferencedNodes, FindByIdentifier {
    // alte Impl. des Content Subgraphen
}

class VirtualizedChildren implements FindChildNodes {
    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface {
    }
}

/**
 * The interface to be implemented by content subgraphs
 */
interface ContentSubgraphInterface extends \JsonSerializable
{
    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findChildNodes(NodeInterface $nodeAggregateIdentifier, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateAggregateIdentifier
     * @param PropertyName|null $name
     * @return NodeInterface[]
     */
    public function findReferencedNodes(NodeInterface $nodeAggregateAggregateIdentifier, PropertyName $name = null): array;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param PropertyName $name
     * @return NodeInterface[]
     */
    public function findReferencingNodes(NodeInterface $nodeAggregateIdentifier, PropertyName $name = null): array;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @return int
     */
    public function countChildNodes(NodeInterface $parentNodeAggregateIdentifier, NodeTypeConstraints $nodeTypeConstraints = null): int;

    /**
     * @param NodeAggregateIdentifier $childAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findParentNode(NodeInterface $childAggregateIdentifier): ?NodeInterface;

    /**
     * @param NodePath $path
     * @param NodeAggregateIdentifier $startingNodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByPath(NodePath $path, NodeInterface $startingNodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $edgeName
     * @return NodeInterface|null
     */
    public function findChildNodeConnectedThroughEdgeName(NodeInterface $parentNodeAggregateIdentifier, NodeName $edgeName): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findSiblings(NodeInterface $sibling, ?NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findSucceedingSiblings(NodeInterface $sibling, ?NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findPrecedingSiblings(NodeInterface $sibling, ?NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodePath
     */
    public function findNodePath(NodeAggregateIdentifier $nodeAggregateIdentifier): NodePath;

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier;

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint;

    /**
     * @param NodeAggregateIdentifier[] $entryNodeAggregateIdentifiers
     * @param int $maximumLevels
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @return mixed
     */
    public function findSubtrees(array $entryNodeAggregateIdentifiers, int $maximumLevels, NodeTypeConstraints $nodeTypeConstraints): SubtreeInterface;

    /**
     * Recursively find all nodes underneath the $entryNodeAggregateIdentifiers, which match the node type constraints specified by NodeTypeConstraints.
     *
     * If a Search Term is specified, the properties are searched for this search term.
     *
     * @param array $entryNodeAggregateIdentifiers
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param SearchTerm|null $searchTerm
     * @return array|NodeInterface[]
     */
    public function findDescendants(array $entryNodeAggregateIdentifiers, NodeTypeConstraints $nodeTypeConstraints, ?SearchTerm $searchTerm): array;

    public function countNodes(): int;

    public function getInMemoryCache(): InMemoryCache;
}
