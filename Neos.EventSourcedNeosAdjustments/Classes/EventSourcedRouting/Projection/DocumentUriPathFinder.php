<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\ValueObject\DocumentNodeInfo;
use Neos\Flow\Annotations as Flow;

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

    public function getLiveContentStreamIdentifier(): ContentStreamIdentifier
    {
        if ($this->liveContentStreamIdentifierRuntimeCache === null) {
            $this->liveContentStreamIdentifierRuntimeCache = ContentStreamIdentifier::fromString($this->dbal->fetchColumn('SELECT contentStreamIdentifier FROM ' . DocumentUriPathProjector::TABLE_NAME_LIVE_CONTENT_STREAMS));
        }
        return $this->liveContentStreamIdentifierRuntimeCache;
    }

    public function getNodeInfoForNodeAddress(NodeAddress  $nodeAddress): DocumentNodeInfo
    {
        $row = $this->dbal->fetchAssoc('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacepointHash = :dimensionSpacepointHash AND nodeAggregateIdentifier = :nodeAggregateIdentifier AND disabled = 0', [
            'dimensionSpacepointHash' => $nodeAddress->getDimensionSpacePoint()->getHash(),
            'nodeAggregateIdentifier' => $nodeAddress->getNodeAggregateIdentifier(),
        ]);
        return $this->databaseRowToDocumentNodeInfo($row);
    }

    public function getParentNodeInfo(DocumentNodeInfo $nodeInfo): DocumentNodeInfo
    {
        $row = $this->dbal->fetchAssoc('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacepointHash = :dimensionSpacepointHash AND nodeAggregateIdentifier = :nodeAggregateIdentifier AND disabled = 0', [
            'dimensionSpacepointHash' => $nodeInfo->getDimensionSpacePointHash(),
            'nodeAggregateIdentifier' => $nodeInfo->getParentNodeAggregateIdentifier(),
        ]);
        return $this->databaseRowToDocumentNodeInfo($row);
    }

    public function getFirstChildNodeInfo(DocumentNodeInfo $nodeInfo): DocumentNodeInfo
    {
        $row = $this->dbal->fetchAssoc('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacepointHash = :dimensionSpacepointHash AND parentNodeAggregateIdentifier = :parentNodeAggregateIdentifier AND precedingNodeAggregateIdentifier IS NULL AND disabled = 0', [
            'dimensionSpacepointHash' => $nodeInfo->getDimensionSpacePointHash(),
            'parentNodeAggregateIdentifier' => $nodeInfo->getNodeAggregateIdentifier(),
        ]);
        return $this->databaseRowToDocumentNodeInfo($row);
    }

    public function getNodeInfoForSiteNodeNameAndUriPath(NodeName $siteNodeName, string $uriPath, DimensionSpacePoint $dimensionSpacePoint): DocumentNodeInfo
    {
        $row = $this->dbal->fetchAssoc('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacepointHash = :dimensionSpacepointHash AND siteNodeName = :siteNodeName AND uriPath = :uriPath AND disabled = 0', [
            'dimensionSpacepointHash' => $dimensionSpacePoint->getHash(),
            'siteNodeName' => $siteNodeName,
            'uriPath' => $uriPath,
        ]);
        return $this->databaseRowToDocumentNodeInfo($row);
    }

    /**
     * @param array|false|null $row
     * @return DocumentNodeInfo
     */
    private function databaseRowToDocumentNodeInfo($row): DocumentNodeInfo
    {
        if (!is_array($row)) {
            // TODO
            throw new \InvalidArgumentException('TODO');
        }
        return DocumentNodeInfo::fromDatabaseRow($row);
    }
}
