<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjectionFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphSchemaBuilder;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\TestingNodeAggregateId;
use Neos\ContentRepositoryRegistry\Infrastructure\DbalClient;
use Neos\Flow\Utility\Algorithms;

/**
 * Custom context trait for projection integrity violation detection specific to the Doctrine DBAL content graph adapter
 */
trait ProjectionIntegrityViolationDetectionTrait
{
    private DbalClient $dbalClient;

    abstract protected function getContentRepositoryId(): ContentRepositoryId;

    protected function getTableNamePrefix(): string
    {
        return DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix($this->getContentRepositoryId());
    }

    public function setupDbalGraphAdapterIntegrityViolationTrait()
    {
        $this->dbalClient = $this->getObjectManager()->get(DbalClient::class);
    }

    /**
     * @When /^I remove the following restriction relation:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     * @throws InvalidArgumentException
     */
    public function iRemoveTheFollowingRestrictionRelation(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);
        $record = $this->transformDatasetToRestrictionRelationRecord($dataset);

        $this->dbalClient->getConnection()->delete(
            $this->getTableNamePrefix() . '_restrictionrelation',
            $record
        );
    }

    /**
     * @When /^I detach the following restriction relation from its origin:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     */
    public function iDetachTheFollowingRestrictionRelationFromItsOrigin(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);
        $record = $this->transformDatasetToRestrictionRelationRecord($dataset);
        $this->dbalClient->getConnection()->update(
            $this->getTableNamePrefix() . '_restrictionrelation',
            [
                'originnodeaggregateid' => (string)TestingNodeAggregateId::nonExistent()
            ],
            $record
        );
    }

    /**
     * @When /^I detach the following restriction relation from its target:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     */
    public function iDetachTheFollowingRestrictionRelationFromItsTarget(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);
        $record = $this->transformDatasetToRestrictionRelationRecord($dataset);
        $this->dbalClient->getConnection()->update(
            $this->getTableNamePrefix() . '_restrictionrelation',
            [
                'affectednodeaggregateid' => (string)TestingNodeAggregateId::nonExistent()
            ],
            $record
        );
    }

    /**
     * @When /^I add the following hierarchy relation:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     */
    public function iAddTheFollowingHierarchyRelation(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);
        $record = $this->transformDatasetToHierarchyRelationRecord($dataset);
        $this->dbalClient->getConnection()->insert(
            $this->getTableNamePrefix() . '_hierarchyrelation',
            $record
        );
    }

    /**
     * @When /^I change the following hierarchy relation's dimension space point hash:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     */
    public function iChangeTheFollowingHierarchyRelationsDimensionSpacePointHash(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);
        $record = $this->transformDatasetToHierarchyRelationRecord($dataset);
        unset($record['position']);

        $this->dbalClient->getConnection()->update(
            $this->getTableNamePrefix() . '_hierarchyrelation',
            [
                'dimensionspacepointhash' => $dataset['newDimensionSpacePointHash']
            ],
            $record
        );
    }

    /**
     * @When /^I set the following position:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     */
    public function iSetTheFollowingPosition(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($dataset['dimensionSpacePoint']);
        $record = [
            'contentstreamid' => $dataset['contentStreamId'],
            'dimensionspacepointhash' => $dimensionSpacePoint->hash,
            'childnodeanchor' => $this->findRelationAnchorPointByDataset($dataset)
        ];

        $this->dbalClient->getConnection()->update(
            $this->getTableNamePrefix() . '_hierarchyrelation',
            [
                'position' => $dataset['newPosition']
            ],
            $record
        );
    }

    /**
     * @When /^I detach the following reference relation from its source:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     */
    public function iDetachTheFollowingReferenceRelationFromItsSource(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);

        $this->dbalClient->getConnection()->update(
            $this->getTableNamePrefix() . '_referencerelation',
            [
                'nodeanchorpoint' => 'detached'
            ],
            $this->transformDatasetToReferenceRelationRecord($dataset)
        );
    }

    /**
     * @When /^I set the following reference position:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     */
    public function iSetTheFollowingReferencePosition(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);

        $this->dbalClient->getConnection()->update(
            $this->getTableNamePrefix() . '_referencerelation',
            [
                'position' => $dataset['newPosition']
            ],
            $this->transformDatasetToReferenceRelationRecord($dataset)
        );
    }

    private function transformDatasetToReferenceRelationRecord(array $dataset): array
    {
        return [
            'name' => $dataset['referenceName'],
            'nodeanchorpoint' => $this->findRelationAnchorPointByIds(
                ContentStreamId::fromString($dataset['contentStreamId']),
                DimensionSpacePoint::fromArray($dataset['dimensionSpacePoint']),
                NodeAggregateId::fromString($dataset['sourceNodeAggregateId'])
            ),
            'destinationnodeaggregateid' => $dataset['destinationNodeAggregateId']
        ];
    }

    private function transformDatasetToRestrictionRelationRecord(array $dataset): array
    {
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($dataset['dimensionSpacePoint']);

        return [
            'contentstreamid' => $dataset['contentStreamId'],
            'dimensionspacepointhash' => $dimensionSpacePoint->hash,
            'originnodeaggregateid' => $dataset['originNodeAggregateId'],
            'affectednodeaggregateid' => $dataset['affectedNodeAggregateId'],
        ];
    }

    private function transformDatasetToHierarchyRelationRecord(array $dataset): array
    {
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($dataset['dimensionSpacePoint']);
        $parentNodeAggregateId = TestingNodeAggregateId::fromString($dataset['parentNodeAggregateId']);
        $childAggregateId = TestingNodeAggregateId::fromString($dataset['childNodeAggregateId']);

        return [
            'contentstreamid' => $dataset['contentStreamId'],
            'dimensionspacepoint' => \json_encode($dimensionSpacePoint),
            'dimensionspacepointhash' => $dimensionSpacePoint->hash,
            'parentnodeanchor' => $parentNodeAggregateId->isNonExistent()
                ? Algorithms::generateUUID()
                : $this->findRelationAnchorPointByIds(
                    ContentStreamId::fromString($dataset['contentStreamId']),
                    $dimensionSpacePoint,
                    NodeAggregateId::fromString($dataset['parentNodeAggregateId'])
                ),
            'childnodeanchor' => $childAggregateId->isNonExistent()
                ? Algorithms::generateUUID()
                : $this->findRelationAnchorPointByIds(
                    ContentStreamId::fromString($dataset['contentStreamId']),
                    $dimensionSpacePoint,
                    NodeAggregateId::fromString($dataset['childNodeAggregateId'])
                ),
            'position' => $dataset['position'] ?? 0
        ];
    }

    private function findRelationAnchorPointByDataset(array $dataset): string
    {
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($dataset['originDimensionSpacePoint'] ?? $dataset['dimensionSpacePoint']);

        return $this->findRelationAnchorPointByIds(
            ContentStreamId::fromString($dataset['contentStreamId']),
            $dimensionSpacePoint,
            NodeAggregateId::fromString($dataset['nodeAggregateId'] ?? $dataset['childNodeAggregateId'])
        );
    }

    private function findRelationAnchorPointByIds(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): string {
        $nodeRecord = $this->dbalClient->getConnection()->executeQuery(
            'SELECT n.relationanchorpoint
                            FROM ' . $this->getTableNamePrefix() . '_node n
                            INNER JOIN ' . $this->getTableNamePrefix() . '_hierarchyrelation h
                            ON n.relationanchorpoint = h.childnodeanchor
                            WHERE n.nodeaggregateid = :nodeAggregateId
                            AND h.contentstreamid = :contentStreamId
                            AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'contentStreamId' => (string)$contentStreamId,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateId' => (string)$nodeAggregateId
            ]
        )->fetchAssociative();

        return $nodeRecord['relationanchorpoint'];
    }

    private function transformPayloadTableToDataset(TableNode $payloadTable): array
    {
        $result = [];
        foreach ($payloadTable as $row) {
            $result[$row['Key']] = \json_decode($row['Value'], true);
        }

        return $result;
    }
}
