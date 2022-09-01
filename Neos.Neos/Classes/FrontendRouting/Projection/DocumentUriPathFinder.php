<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Projection;

use Neos\Flow\Annotations as Flow;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;

/**
 * @Flow\Proxy(false)
 */
final class DocumentUriPathFinder implements ProjectionStateInterface
{
    private ?ContentStreamId $liveContentStreamIdRuntimeCache = null;

    public function __construct(
        private readonly Connection $dbal,
        private readonly string $tableNamePrefix
    ) {
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
     * Returns the DocumentNodeInfo of a node for the given $nodeAggregateId
     * Note: This will not exclude *disabled* nodes in order to allow the calling side
     * to make a distinction (e.g. in order to display a custom error)
     *
     * @param NodeAggregateId $nodeAggregateId
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException if no matching DocumentNodeInfo can be found
     *  (node doesn't exist in live workspace, projection not up to date)
     */
    public function getByIdAndDimensionSpacePointHash(
        NodeAggregateId $nodeAggregateId,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'nodeAggregateId = :nodeAggregateId
                AND dimensionSpacePointHash = :dimensionSpacePointHash',
            compact('nodeAggregateId', 'dimensionSpacePointHash')
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
            $nodeInfo->getParentNodeAggregateId(),
            $nodeInfo->getDimensionSpacePointHash()
        );
    }

    /**
     * Returns the preceding DocumentNodeInfo for $succeedingNodeAggregateId
     * and the $parentNodeAggregateId (= node on the same hierarchy level)
     * Note: This will not exclude *disabled* nodes in order to allow the calling side
     * to make a distinction (e.g. in order to display a custom error)
     *
     * @param NodeAggregateId $succeedingNodeAggregateId
     * @param NodeAggregateId $parentNodeAggregateId
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException if no preceding DocumentNodeInfo can be found
     *  (given $succeedingNodeAggregateId doesn't exist or refers to the first/only node
     *  with the given $parentNodeAggregateId)
     */
    public function getPrecedingNode(
        NodeAggregateId $succeedingNodeAggregateId,
        NodeAggregateId $parentNodeAggregateId,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'dimensionSpacePointHash = :dimensionSpacePointHash
                AND parentNodeAggregateId = :parentNodeAggregateId
                AND succeedingNodeAggregateId = :succeedingNodeAggregateId',
            compact(
                'dimensionSpacePointHash',
                'parentNodeAggregateId',
                'succeedingNodeAggregateId'
            )
        );
    }

    /**
     * Returns the DocumentNodeInfo for the first *enabled* child node for the specified $parentNodeAggregateId
     * Note: This will not exclude *disabled* nodes in order to allow the calling side to make a distinction
     * (e.g. in order to display a custom error)
     *
     * @param NodeAggregateId $parentNodeAggregateId
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getFirstEnabledChildNode(
        NodeAggregateId $parentNodeAggregateId,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'dimensionSpacePointHash = :dimensionSpacePointHash
                AND parentNodeAggregateId = :parentNodeAggregateId
                AND precedingNodeAggregateId IS NULL
                AND disabled = 0',
            compact('dimensionSpacePointHash', 'parentNodeAggregateId')
        );
    }

    /**
     * @param NodeAggregateId $parentNodeAggregateId
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getLastChildNode(
        NodeAggregateId $parentNodeAggregateId,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'dimensionSpacePointHash = :dimensionSpacePointHash
                AND parentNodeAggregateId = :parentNodeAggregateId
                AND succeedingNodeAggregateId IS NULL',
            compact('dimensionSpacePointHash', 'parentNodeAggregateId')
        );
    }

    /**
     * @param NodeAggregateId $nodeAggregateId
     * @return \Iterator|DocumentNodeInfo[]
     */
    public function getNodeVariantsById(NodeAggregateId $nodeAggregateId): \Iterator
    {
        try {
            $iterator = $this->dbal->executeQuery(
                'SELECT * FROM ' . $this->tableNamePrefix . '_uri
                     WHERE nodeAggregateId = :nodeAggregateId',
                ['nodeAggregateId' => $nodeAggregateId]
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to get node variants for id "%s": %s',
                $nodeAggregateId,
                $e->getMessage()
            ), 1599665543, $e);
        }
        foreach ($iterator as $nodeSource) {
            yield DocumentNodeInfo::fromDatabaseRow($nodeSource);
        }
    }

    public function getLiveContentStreamId(): ContentStreamId
    {
        if ($this->liveContentStreamIdRuntimeCache === null) {
            try {
                $contentStreamId = $this->dbal->fetchColumn(
                    'SELECT contentStreamId FROM '
                        . $this->tableNamePrefix . '_livecontentstreams LIMIT 1'
                );
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf(
                    'Failed to fetch contentStreamId for live workspace: %s',
                    $e->getMessage()
                ), 1599666764, $e);
            }
            if (!is_string($contentStreamId)) {
                throw new \RuntimeException(
                    'Failed to fetch contentStreamId for live workspace,'
                        . ' probably you have to replay the "documenturipath" projection',
                    1599667894
                );
            }
            $this->liveContentStreamIdRuntimeCache
                = ContentStreamId::fromString($contentStreamId);
        }
        return $this->liveContentStreamIdRuntimeCache;
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
