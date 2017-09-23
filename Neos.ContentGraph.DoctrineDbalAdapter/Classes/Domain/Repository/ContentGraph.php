<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Service\DbalClient;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @Flow\Scope("singleton")
 * @api
 */
final class ContentGraph implements ContentGraphInterface
{
    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var array|ContentSubgraphInterface[]
     */
    protected $subgraphs;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return ContentSubgraphInterface|null
     */
    final public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): ?ContentSubgraphInterface
    {
        $index = (string)$contentStreamIdentifier . '-' . $dimensionSpacePoint->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraph($contentStreamIdentifier, $dimensionSpacePoint);
        }

        return $this->subgraphs[$index];
    }

    /**
     * @return array|ContentSubgraphInterface[]
     */
    final public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }

    /**
     * Find a node by node identifier and content stream identifier
     *
     * Note: This does not pass the CR context to the node!!!
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByIdentifierInContentStream(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier): ?NodeInterface
    {
        $connection = $this->client->getConnection();

        // HINT: we check the ContentStreamIdentifier on the EDGE; as this is where we actually find out whether the node exists in the content stream
        $nodeRow = $connection->executeQuery(
            'SELECT n.*, h.contentstreamidentifier, h.name FROM neos_contentgraph_node n
                  INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                  WHERE n.nodeidentifier = :nodeIdentifier
                  AND h.contentstreamidentifier = :contentStreamIdentifier',
            [
                'nodeIdentifier' => (string)$nodeIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetch();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode($nodeRow, null) : null;
    }
}
