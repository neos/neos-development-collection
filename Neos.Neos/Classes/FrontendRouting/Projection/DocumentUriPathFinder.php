<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Neos\ContentRepository\Projection\ProjectionStateInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;

final class DocumentUriPathFinder implements ProjectionStateInterface
{
    private ?ContentStreamIdentifier $liveContentStreamIdentifierRuntimeCache = null;

    public function __construct(
        private readonly Connection $dbal,
        private readonly string $tableNamePrefix
    )
    {
    }

    /**
     * Returns the DocumentNodeInfo of a node for the given $siteNodeName and $uriPath
     *
     * @param SiteNodeName $siteNodeName
     * @param string $uriPath
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException if no matching DocumentNodeInfo can be found
     * (node is disabled, node doesn't exist in live workspace, projection not up to date)
     */
    public function getEnabledBySiteNodeNameUriPathAndDimensionSpacePointHash(
        SiteNodeName $siteNodeName,
        string $uriPath,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'dimensionSpacePointHash = :dimensionSpacePointHash
                AND siteNodeName = :siteNodeName
                AND uriPath = :uriPath
                AND disabled = 0',
            compact('dimensionSpacePointHash', 'siteNodeName', 'uriPath')
        );
    }

    /**
     * Returns the DocumentNodeInfo of a node for the given $nodeAggregateIdentifier
     * Note: This will not exclude *disabled* nodes in order to allow the calling side
     * to make a distinction (e.g. in order to display a custom error)
     *
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException if no matching DocumentNodeInfo can be found
     *  (node doesn't exist in live workspace, projection not up to date)
     */
    public function getByIdAndDimensionSpacePointHash(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'nodeAggregateIdentifier = :nodeAggregateIdentifier
                AND dimensionSpacePointHash = :dimensionSpacePointHash',
            compact('nodeAggregateIdentifier', 'dimensionSpacePointHash')
        );
    }

    /**
     * Returns the parent DocumentNodeInfo of a node for the given $nodeInfo
     * Note: This will not exclude *disabled* nodes in order to allow the calling side to make a distinction
     * (e.g. in order to display a custom error)
     *
     * @param DocumentNodeInfo $nodeInfo
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException if no matching DocumentNodeInfo can be found
     *  (given $nodeInfo belongs to a site root node, projection not up to date)
     */
    public function getParentNode(DocumentNodeInfo $nodeInfo): DocumentNodeInfo
    {
        return $this->getByIdAndDimensionSpacePointHash(
            $nodeInfo->getParentNodeAggregateIdentifier(),
            $nodeInfo->getDimensionSpacePointHash()
        );
    }

    /**
     * Returns the preceding DocumentNodeInfo for $succeedingNodeAggregateIdentifier
     * and the $parentNodeAggregateIdentifier (= node on the same hierarchy level)
     * Note: This will not exclude *disabled* nodes in order to allow the calling side
     * to make a distinction (e.g. in order to display a custom error)
     *
     * @param NodeAggregateIdentifier $succeedingNodeAggregateIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException if no preceding DocumentNodeInfo can be found
     *  (given $succeedingNodeAggregateIdentifier doesn't exist or refers to the first/only node
     *  with the given $parentNodeAggregateIdentifier)
     */
    public function getPrecedingNode(
        NodeAggregateIdentifier $succeedingNodeAggregateIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'dimensionSpacePointHash = :dimensionSpacePointHash
                AND parentNodeAggregateIdentifier = :parentNodeAggregateIdentifier
                AND succeedingNodeAggregateIdentifier = :succeedingNodeAggregateIdentifier',
            compact(
                'dimensionSpacePointHash',
                'parentNodeAggregateIdentifier',
                'succeedingNodeAggregateIdentifier'
            )
        );
    }

    /**
     * Returns the DocumentNodeInfo for the first *enabled* child node for the specified $parentNodeAggregateIdentifier
     * Note: This will not exclude *disabled* nodes in order to allow the calling side to make a distinction
     * (e.g. in order to display a custom error)
     *
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getFirstEnabledChildNode(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'dimensionSpacePointHash = :dimensionSpacePointHash
                AND parentNodeAggregateIdentifier = :parentNodeAggregateIdentifier
                AND precedingNodeAggregateIdentifier IS NULL
                AND disabled = 0',
            compact('dimensionSpacePointHash', 'parentNodeAggregateIdentifier')
        );
    }

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getLastChildNode(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'dimensionSpacePointHash = :dimensionSpacePointHash
                AND parentNodeAggregateIdentifier = :parentNodeAggregateIdentifier
                AND succeedingNodeAggregateIdentifier IS NULL',
            compact('dimensionSpacePointHash', 'parentNodeAggregateIdentifier')
        );
    }

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return \Iterator|DocumentNodeInfo[]
     */
    public function getNodeVariantsById(NodeAggregateIdentifier $nodeAggregateIdentifier): \Iterator
    {
        try {
            $iterator = $this->dbal->executeQuery(
                'SELECT * FROM ' . $this->tableNamePrefix . '_uri
                     WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier',
                ['nodeAggregateIdentifier' => $nodeAggregateIdentifier]
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to get node variants for id "%s": %s',
                $nodeAggregateIdentifier,
                $e->getMessage()
            ), 1599665543, $e);
        }
        foreach ($iterator as $nodeSource) {
            yield DocumentNodeInfo::fromDatabaseRow($nodeSource);
        }
    }

    public function getLiveContentStreamIdentifier(): ContentStreamIdentifier
    {
        if ($this->liveContentStreamIdentifierRuntimeCache === null) {
            try {
                $contentStreamIdentifier = $this->dbal->fetchColumn(
                    'SELECT contentStreamIdentifier FROM '
                        . $this->tableNamePrefix. '_livecontentstreams LIMIT 1'
                );
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf(
                    'Failed to fetch contentStreamIdentifier for live workspace: %s',
                    $e->getMessage()
                ), 1599666764, $e);
            }
            if (!is_string($contentStreamIdentifier)) {
                throw new \RuntimeException(
                    'Failed to fetch contentStreamIdentifier for live workspace,'
                        . ' probably you have to replay the "documenturipath" projection',
                    1599667894
                );
            }
            $this->liveContentStreamIdentifierRuntimeCache
                = ContentStreamIdentifier::fromString($contentStreamIdentifier);
        }
        return $this->liveContentStreamIdentifierRuntimeCache;
    }

    /**
     * @param array<string,mixed> $parameters
     * @throws NodeNotFoundException
     */
    private function fetchSingle(string $where, array $parameters): DocumentNodeInfo
    {
        # NOTE: "LIMIT 1" in the following query is just a performance optimization
        # since Connection::fetchAssoc() only returns the first result anyways
        try {
            $row = $this->dbal->fetchAssoc(
                'SELECT * FROM ' . $this->tableNamePrefix . '_uri
                     WHERE ' . $where . ' LIMIT 1',
                $parameters,
                DocumentUriPathProjection::COLUMN_TYPES_DOCUMENT_URIS
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to load node for query "%s": %s',
                $where,
                $e->getMessage()
            ), 1599664746, $e);
        }
        if ($row === false) {
            throw new NodeNotFoundException(sprintf('No matching node found for query "%s"', $where), 1599667143);
        }
        return DocumentNodeInfo::fromDatabaseRow($row);
    }
}
