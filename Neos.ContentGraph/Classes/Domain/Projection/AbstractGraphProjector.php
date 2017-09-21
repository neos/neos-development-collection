<?php

namespace Neos\ContentGraph\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentGraph\Infrastructure;
use Neos\ContentRepository\Domain\Context\Importing\Event\NodeWasImported;
use Neos\ContentRepository\Domain\Context\Node\Event;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\SubgraphIdentifierSet;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The abstract alternate reality-aware graph projector to be used for arbitrary database back ends
 */
abstract class AbstractGraphProjector implements ProjectorInterface
{
    final public function whenRootNodeWasCreated(Event\RootNodeWasCreated $event)
    {
        $node = Node::fromRootNodeWasCreated($event);

        $this->transactional(function () use ($node) {
            $this->addNode($node);
        });
    }

    final public function whenNodeAggregateWithNodeWasCreated(Event\NodeAggregateWithNodeWasCreated $event)
    {
        $node = Node::fromNodeAggregateWithNodeWasCreated($event);

        $this->transactional(function () use ($node, $event) {
            $this->addNode($node);
            $this->connectHierarchy(
                $event->getParentNodeIdentifier(),
                $event->getNodeIdentifier(),
                // TODO: position on insert is still missing
                null,
                $event->getNodeName(),
                new SubgraphIdentifierSet($event->getContentStreamIdentifier(), $event->getVisibleDimensionSpacePoints())
            );
        });
    }

    /*final public function whenNodeWasImported(NodeWasImported $event)
    {
        $node = Infrastructure\Dto\Node::fromNodeWasImported($event);

        /*
        $this->transactional(function () use ($node, $parentsIdentifierInGraph, $elderSiblingsIdentifierInGraph, $subgraphIdentifiers) {
            $this->addNode($node);
            $this->connectHierarchy(
                $parentsIdentifierInGraph,
                $node->identifierInGraph,
                $elderSiblingsIdentifierInGraph,
                null,
                $subgraphIdentifiers
            );
        });

        $this->transactional(function () use ($node) {
            $this->addNode($node);
        });

    }*/

    final public function whenNodePropertyWasSet(Event\NodePropertyWasSet $event)
    {

    }

    /*

    final public function whenSystemNodeWasInserted(Event\SystemNodeWasInserted $event)
    {
        $systemNode = Node::fromSystemNodeWasInserted($event);
        $this->transactional(function () use ($systemNode) {
            $this->addNode($systemNode);
        });
    }

    final public function whenNodeWasInserted(Event\NodeWasInserted $event)
    {
        $node = Node::fromNodeWasInserted($event);
        $parentsIdentifierInGraph = $event->getParentIdentifier();
        $elderSiblingsIdentifierInGraph = $event->getElderSiblingIdentifier();

        $subgraphIdentifiers = $this->fallbackGraphService->determineAffectedVariantSubgraphIdentifiers($node->subgraphIdentifier);
        // @todo handle reference edges

        $this->transactional(function () use ($node, $parentsIdentifierInGraph, $elderSiblingsIdentifierInGraph, $subgraphIdentifiers) {
            $this->addNode($node);
            $this->connectHierarchy(
                $parentsIdentifierInGraph,
                $node->identifierInGraph,
                $elderSiblingsIdentifierInGraph,
                null,
                $subgraphIdentifiers
            );
        });
    }
final public function whenNodeVariantWasCreated(Event\NodeVariantWasCreated $event)
{
    $fallbackNode = $this->getNode($event->getFallbackIdentifier());
    $variantNode = Node::fromNodeVariantWasCreated($event, $fallbackNode);

    $subgraphIdentifiers = $this->fallbackGraphService->determineAffectedVariantSubgraphIdentifiers($variantNode->subgraphIdentifier);
    // @todo handle reference edges

    $this->transactional(function () use ($variantNode, $fallbackNode, $subgraphIdentifiers) {
        $this->addNode($variantNode);
        $this->reconnectHierarchy(
            $fallbackNode->identifierInGraph,
            $variantNode->identifierInGraph,
            $subgraphIdentifiers
        );
    });
}
*/

    abstract protected function transactional(callable $operations);

    abstract protected function addNode(Node $node);

    abstract protected function getNode(ContentRepository\ValueObject\NodeAggregateIdentifier $nodeIdentifier, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): Node;

    abstract protected function connectHierarchy(
        NodeIdentifier $parentNodeIdentifier,
        NodeIdentifier $childNodeIdentifier,
        NodeIdentifier $preceedingSiblingNodeIdentifier = null,
        NodeName $edgeName = null,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet
    );

    abstract protected function connectRelation(string $startNodesIdentifierInGraph, string $endNodesIdentifierInGraph, string $relationshipName, array $properties, array $subgraphIdentifiers);

    abstract protected function reconnectHierarchy(string $fallbackNodesIdentifierInGraph, string $newVariantNodesIdentifierInGraph, array $subgraphIdentifiers);
}
