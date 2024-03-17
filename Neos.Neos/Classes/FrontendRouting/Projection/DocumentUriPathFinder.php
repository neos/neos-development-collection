<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;

/**
 * @Flow\Proxy(false)
 */
final class DocumentUriPathFinder implements ProjectionStateInterface
{
    private ?ContentStreamId $liveContentStreamIdRuntimeCache = null;
    private bool $cacheEnabled = true;

    /**
     * @var array<string,DocumentNodeInfo>
     */
    private array $getByIdAndDimensionSpacePointHashCache = [];

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
     * @api
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
            [
                'dimensionSpacePointHash' => $dimensionSpacePointHash,
                'siteNodeName' => $siteNodeName->value,
                'uriPath' => $uriPath,
            ]
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
     * @api
     */
    public function getByIdAndDimensionSpacePointHash(
        NodeAggregateId $nodeAggregateId,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        $cacheKey = $this->calculateCacheKey($nodeAggregateId, $dimensionSpacePointHash);
        if ($this->cacheEnabled && isset($this->getByIdAndDimensionSpacePointHashCache[$cacheKey])) {
            return $this->getByIdAndDimensionSpacePointHashCache[$cacheKey];
        }
        $result = $this->fetchSingle(
            'nodeAggregateId = :nodeAggregateId
                AND dimensionSpacePointHash = :dimensionSpacePointHash',
            [
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHash' => $dimensionSpacePointHash,
            ]
        );
        if ($this->cacheEnabled) {
            $this->getByIdAndDimensionSpacePointHashCache[$cacheKey] = $result;
        }
        return $result;
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
     * @api
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
     * @internal
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
            [
                'dimensionSpacePointHash' => $dimensionSpacePointHash,
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
                'succeedingNodeAggregateId' => $succeedingNodeAggregateId->value,
            ]
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
     * @api
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
            [
                'dimensionSpacePointHash' => $dimensionSpacePointHash,
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
            ]
        );
    }

    /**
     * @param NodeAggregateId $parentNodeAggregateId
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     * @internal
     */
    public function getLastChildNode(
        NodeAggregateId $parentNodeAggregateId,
        string $dimensionSpacePointHash
    ): DocumentNodeInfo {
        return $this->fetchSingle(
            'dimensionSpacePointHash = :dimensionSpacePointHash
                AND parentNodeAggregateId = :parentNodeAggregateId
                AND succeedingNodeAggregateId IS NULL',
            [
                'dimensionSpacePointHash' => $dimensionSpacePointHash,
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
            ]
        );
    }

    /**
     * @api
     */
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
            $row = $this->dbal->fetchAssociative(
                'SELECT * FROM ' . $this->tableNamePrefix . '_uri
                     WHERE ' . $where . ' LIMIT 1',
                $parameters,
                DocumentUriPathProjection::COLUMN_TYPES_DOCUMENT_URIS
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to fetch a node, please ensure the projection is setup. Query "%s". %s',
                $where,
                $e->getMessage()
            ), 1599664746, $e);
        }
        if ($row === false) {
            throw new NodeNotFoundException(sprintf(
                'No matching node found for query "%s" with params %s',
                $where,
                json_encode($parameters)
            ), 1599667143);
        }
        return DocumentNodeInfo::fromDatabaseRow($row);
    }

    /**
     * @param string $where
     * @param array<string> $parameters
     * @return DocumentNodeInfos
     */
    private function fetchMultiple(string $where, array $parameters): DocumentNodeInfos
    {
        try {
            $rows = $this->dbal->fetchAllAssociative(
                'SELECT * FROM ' . $this->tableNamePrefix . '_uri
                     WHERE ' . $where,
                $parameters,
                DocumentUriPathProjection::COLUMN_TYPES_DOCUMENT_URIS
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to fetch multiple nodes, please ensure the projection is setup. Query "%s". %s',
                $where,
                $e->getMessage()
            ), 1683808640, $e);
        }

        return DocumentNodeInfos::create(
            array_map(DocumentNodeInfo::fromDatabaseRow(...), $rows)
        );
    }

    private function calculateCacheKey(NodeAggregateId $nodeAggregateId, string $dimensionSpacePointHash): string
    {
        return $nodeAggregateId->value . '#' . $dimensionSpacePointHash;
    }

    public function purgeCacheFor(DocumentNodeInfo $nodeInfo): void
    {
        if ($this->cacheEnabled) {
            $cacheKey = $this->calculateCacheKey($nodeInfo->getNodeAggregateId(), $nodeInfo->getDimensionSpacePointHash());
            unset($this->getByIdAndDimensionSpacePointHashCache[$cacheKey]);
        }
    }

    public function disableCache(): void
    {
        $this->cacheEnabled = false;
        $this->getByIdAndDimensionSpacePointHashCache = [];
    }

    /**
     * Returns the DocumentNodeInfos of all descendants of a given node.
     * Note: This will not exclude *disabled* nodes in order to allow the calling side
     * to make a distinction (e.g. in order to display a custom error)
     *
     * @param DocumentNodeInfo $node
     * @return DocumentNodeInfos
     */
    public function getDescendantsOfNode(DocumentNodeInfo $node): DocumentNodeInfos
    {
        return $this->fetchMultiple(
            'dimensionSpacePointHash = :dimensionSpacePointHash
            AND nodeAggregateIdPath LIKE :childNodeAggregateIdPathPrefix',
            [
                'dimensionSpacePointHash' => $node->getDimensionSpacePointHash(),
                'childNodeAggregateIdPathPrefix' => $node->getNodeAggregateIdPath() . '/%',
            ]
        );
    }

    public function isLiveContentStream(ContentStreamId $contentStreamId): bool
    {
        return $contentStreamId->equals($this->getLiveContentStreamId());
    }
}
