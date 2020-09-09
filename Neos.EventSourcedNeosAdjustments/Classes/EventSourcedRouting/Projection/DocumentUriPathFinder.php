<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\ValueObject\DocumentNodeInfo;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\Exception\NodeNotFoundException;

/**
 * @Flow\Scope("singleton")
 */
final class DocumentUriPathFinder
{
    private Connection $dbal;

    private ?ContentStreamIdentifier $liveContentStreamIdentifierRuntimeCache = null;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
    }

    /**
     * @param NodeName $siteNodeName
     * @param string $uriPath
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getEnabledBySiteNodeNameUriPathAndDimensionSpacePointHash(NodeName $siteNodeName, string $uriPath, string $dimensionSpacePointHash): DocumentNodeInfo
    {
        return $this->fetchSingle('dimensionSpacePointHash = :dimensionSpacePointHash AND siteNodeName = :siteNodeName AND uriPath = :uriPath AND disabled = 0', compact('dimensionSpacePointHash', 'siteNodeName', 'uriPath'));
    }

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getOneByIdAndDimensionSpacePointHash(NodeAggregateIdentifier $nodeAggregateIdentifier, string $dimensionSpacePointHash): DocumentNodeInfo
    {
        return $this->fetchSingle('nodeAggregateIdentifier = :nodeAggregateIdentifier AND dimensionSpacePointHash = :dimensionSpacePointHash', compact('nodeAggregateIdentifier', 'dimensionSpacePointHash'));
    }

    /**
     * @param DocumentNodeInfo $nodeInfo
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getParentNode(DocumentNodeInfo $nodeInfo): DocumentNodeInfo
    {
        return $this->getOneByIdAndDimensionSpacePointHash($nodeInfo->getParentNodeAggregateIdentifier(), $nodeInfo->getDimensionSpacePointHash());
    }

    /**
     * @param NodeAggregateIdentifier $succeedingNodeAggregateIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getPrecedingNode(NodeAggregateIdentifier $succeedingNodeAggregateIdentifier, NodeAggregateIdentifier $parentNodeAggregateIdentifier, string $dimensionSpacePointHash): DocumentNodeInfo
    {
        return $this->fetchSingle('dimensionSpacePointHash = :dimensionSpacePointHash AND parentNodeAggregateIdentifier = :parentNodeAggregateIdentifier AND succeedingNodeAggregateIdentifier = :succeedingNodeAggregateIdentifier', compact('dimensionSpacePointHash', 'parentNodeAggregateIdentifier', 'succeedingNodeAggregateIdentifier'));
    }

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getFirstEnabledChildNode(NodeAggregateIdentifier $parentNodeAggregateIdentifier, string $dimensionSpacePointHash): DocumentNodeInfo
    {
        return $this->fetchSingle('dimensionSpacePointHash = :dimensionSpacePointHash AND parentNodeAggregateIdentifier = :parentNodeAggregateIdentifier AND precedingNodeAggregateIdentifier IS NULL AND disabled = 0', compact('dimensionSpacePointHash', 'parentNodeAggregateIdentifier'));
    }

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param string $dimensionSpacePointHash
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    public function getLastChildNode(NodeAggregateIdentifier $parentNodeAggregateIdentifier, string $dimensionSpacePointHash): DocumentNodeInfo
    {
        return $this->fetchSingle('dimensionSpacePointHash = :dimensionSpacePointHash AND parentNodeAggregateIdentifier = :parentNodeAggregateIdentifier AND succeedingNodeAggregateIdentifier IS NULL', compact('dimensionSpacePointHash', 'parentNodeAggregateIdentifier'));
    }

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return \Iterator|DocumentNodeInfo[]
     */
    public function getNodeVariantsById(NodeAggregateIdentifier $nodeAggregateIdentifier): \Iterator
    {
        try {
            $iterator = $this->dbal->executeQuery('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE nodeAggregateIdentifier = :nodeAggregateIdentifier', ['nodeAggregateIdentifier' => $nodeAggregateIdentifier]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to get node variants for id "%s": %s', $nodeAggregateIdentifier, $e->getMessage()), 1599665543, $e);
        }
        foreach ($iterator as $nodeSource) {
            yield DocumentNodeInfo::fromDatabaseRow($nodeSource);
        }
    }

    public function getLiveContentStreamIdentifier(): ContentStreamIdentifier
    {
        if ($this->liveContentStreamIdentifierRuntimeCache === null) {
            try {
                $contentStreamIdentifier = $this->dbal->fetchColumn('SELECT contentStreamIdentifier FROM ' . DocumentUriPathProjector::TABLE_NAME_LIVE_CONTENT_STREAMS);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to fetch contentStreamIdentifier for live workspace: %s', $e->getMessage()), 1599666764, $e);
            }
            if (!is_string($contentStreamIdentifier)) {
                throw new \RuntimeException('Failed to fetch contentStreamIdentifier for live workspace, probably you have to replay the "documenturipath" projection', 1599667894);
            }
            $this->liveContentStreamIdentifierRuntimeCache = ContentStreamIdentifier::fromString($contentStreamIdentifier);
        }
        return $this->liveContentStreamIdentifierRuntimeCache;
    }

    /**
     * @param string $where
     * @param array $parameters
     * @return DocumentNodeInfo
     * @throws NodeNotFoundException
     */
    private function fetchSingle(string $where, array $parameters): DocumentNodeInfo
    {
        try {
            $row = $this->dbal->fetchAssoc('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE ' . $where . ' LIMIT 1', $parameters, DocumentUriPathProjector::COLUMN_TYPES_DOCUMENT_URIS);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load node for query "%s": %s', $where, $e->getMessage()), 1599664746, $e);
        }
        if ($row === false) {
            throw new NodeNotFoundException(sprintf('No matching node found for query "%s"', $where), 1599667143);
        }
        return DocumentNodeInfo::fromDatabaseRow($row);
    }
}
