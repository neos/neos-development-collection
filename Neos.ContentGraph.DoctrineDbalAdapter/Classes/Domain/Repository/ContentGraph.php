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
use Neos\ContentGraph\Domain\Repository\AbstractContentGraph;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content as ContentProjection;
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
final class ContentGraph extends AbstractContentGraph
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

    protected function createSubgraph(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): ContentSubgraphInterface
    {
        return new ContentSubgraph($contentStreamIdentifier, $dimensionSpacePoint);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByIdentifierInContentStream(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier): ?NodeInterface
    {
        $connection = $this->client->getConnection();

        // HINT: we check the ContentStreamIdentifier on the EDGE; as this is where we actually find out whether the node exists in the content stream
        $nodeData = $connection->executeQuery(
            'SELECT n.* FROM neos_contentgraph_node n
                  INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeidentifier = n.nodeidentifier
                  WHERE n.nodeidentifier = :nodeIdentifier
                  AND h.contentstreamidentifier = :contentStreamIdentifier',
            [
                'nodeIdentifier' => (string)$nodeIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetch();

        return $nodeData ? $this->nodeFactory->mapRawDataToNode($nodeData, null) : null;

    }
}
