<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Intermediary\Domain\Exception\NodeImplementationClassNameIsInvalid;
use Neos\ContentRepository\Intermediary\Domain\Exception\PropertyCollectionImplementationClassNameIsInvalid;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\PropertyCollectionImplementationClassNameResolver;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Intermediary\Domain\Property\PropertyConverter;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class ReadModelFactory
{
    protected ContentGraphInterface $contentGraph;

    protected WorkspaceFinder $workspaceFinder;

    protected PropertyConverter $propertyConverter;

    public function __construct(
        ContentGraphInterface $contentGraph,
        WorkspaceFinder $workspaceFinder,
        PropertyConverter $propertyConverter
    ) {
        $this->contentGraph = $contentGraph;
        $this->workspaceFinder = $workspaceFinder;
        $this->propertyConverter = $propertyConverter;
    }

    /**
     * @param NodeInterface $node
     * @param ContentSubgraphInterface $subgraph
     * @return NodeBasedReadModelInterface
     * @throws NodeImplementationClassNameIsInvalid
     * @throws PropertyCollectionImplementationClassNameIsInvalid
     */
    public function createReadModel(NodeInterface $node, ContentSubgraphInterface $subgraph): NodeBasedReadModelInterface
    {
        $implementationClassName = NodeImplementationClassName::forNodeType($node->getNodeType());
        $propertyCollectionClassName = PropertyCollectionImplementationClassNameResolver::forNodeType($node->getNodeType());
        $workspaceName = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($subgraph->getContentStreamIdentifier())->getWorkspaceName();

        $propertyCollection = new $propertyCollectionClassName(
            $node->getProperties(),
            $this->propertyConverter
        );

        return new $implementationClassName(
            $node,
            $subgraph,
            $propertyCollection,
            $workspaceName,
            $this
        );
    }

    public function createReadModelFromNodeAddress(NodeAddress $nodeAddress, VisibilityConstraints $visibilityConstraints): ?NodeBasedReadModelInterface
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $nodeAddress->getContentStreamIdentifier(),
            $nodeAddress->getDimensionSpacePoint(),
            $visibilityConstraints
        );
        if (!$subgraph) {
            return null;
        }
        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->getNodeAggregateIdentifier());

        return $node ? $this->createReadModel($node, $subgraph) : null;
    }

    public function createReadModels(array $nodes, ContentSubgraphInterface $subgraph): NodeBasedReadModels
    {
        $readModels = [];
        foreach ($nodes as $node) {
            $readModels = $this->createReadModel($node, $subgraph);
        }

        return NodeBasedReadModels::fromArray($readModels);
    }
}
