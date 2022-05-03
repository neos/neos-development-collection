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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper\TestingNodeAggregateIdentifier;
use Neos\Flow\Utility\Algorithms;

/**
 * Custom context trait for projection integrity violation detection specific to the Doctrine DBAL content graph adapter
 */
trait ProjectionIntegrityViolationDetectionTrait
{
    private DbalClient $dbalClient;

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
            'neos_contentgraph_restrictionrelation',
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
            'neos_contentgraph_restrictionrelation',
            [
                'originnodeaggregateidentifier' => (string)TestingNodeAggregateIdentifier::nonExistent()
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
            'neos_contentgraph_restrictionrelation',
            [
                'affectednodeaggregateidentifier' => (string)TestingNodeAggregateIdentifier::nonExistent()
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
            'neos_contentgraph_hierarchyrelation',
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
            'neos_contentgraph_hierarchyrelation',
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
            'contentstreamidentifier' => $dataset['contentStreamIdentifier'],
            'dimensionspacepointhash' => $dimensionSpacePoint->hash,
            'childnodeanchor' => $this->findRelationAnchorPointByDataset($dataset)
        ];

        $this->dbalClient->getConnection()->update(
            'neos_contentgraph_hierarchyrelation',
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
            'neos_contentgraph_referencerelation',
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
            'neos_contentgraph_referencerelation',
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
            'nodeanchorpoint' => $this->findRelationAnchorPointByIdentifiers(
                ContentStreamIdentifier::fromString($dataset['contentStreamIdentifier']),
                DimensionSpacePoint::fromArray($dataset['dimensionSpacePoint']),
                NodeAggregateIdentifier::fromString($dataset['sourceNodeAggregateIdentifier'])
            ),
            'destinationnodeaggregateidentifier' => $dataset['destinationNodeAggregateIdentifier']
        ];
    }

    private function transformDatasetToRestrictionRelationRecord(array $dataset): array
    {
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($dataset['dimensionSpacePoint']);

        return [
            'contentstreamidentifier' => $dataset['contentStreamIdentifier'],
            'dimensionspacepointhash' => $dimensionSpacePoint->hash,
            'originnodeaggregateidentifier' => $dataset['originNodeAggregateIdentifier'],
            'affectednodeaggregateidentifier' => $dataset['affectedNodeAggregateIdentifier'],
        ];
    }

    private function transformDatasetToHierarchyRelationRecord(array $dataset): array
    {
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($dataset['dimensionSpacePoint']);
        $parentNodeAggregateIdentifier = TestingNodeAggregateIdentifier::fromString($dataset['parentNodeAggregateIdentifier']);
        $childAggregateIdentifier = TestingNodeAggregateIdentifier::fromString($dataset['childNodeAggregateIdentifier']);

        return [
            'contentstreamidentifier' => $dataset['contentStreamIdentifier'],
            'dimensionspacepoint' => \json_encode($dimensionSpacePoint),
            'dimensionspacepointhash' => $dimensionSpacePoint->hash,
            'parentnodeanchor' => $parentNodeAggregateIdentifier->isNonExistent()
                ? Algorithms::generateUUID()
                : $this->findRelationAnchorPointByIdentifiers(
                    ContentStreamIdentifier::fromString($dataset['contentStreamIdentifier']),
                    $dimensionSpacePoint,
                    NodeAggregateIdentifier::fromString($dataset['parentNodeAggregateIdentifier'])
                ),
            'childnodeanchor' => $childAggregateIdentifier->isNonExistent()
                ? Algorithms::generateUUID()
                : $this->findRelationAnchorPointByIdentifiers(
                    ContentStreamIdentifier::fromString($dataset['contentStreamIdentifier']),
                    $dimensionSpacePoint,
                    NodeAggregateIdentifier::fromString($dataset['childNodeAggregateIdentifier'])
                ),
            'position' => $dataset['position'] ?? 0
        ];
    }

    private function findRelationAnchorPointByDataset(array $dataset): string
    {
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($dataset['originDimensionSpacePoint'] ?? $dataset['dimensionSpacePoint']);

        return $this->findRelationAnchorPointByIdentifiers(
            ContentStreamIdentifier::fromString($dataset['contentStreamIdentifier']),
            $dimensionSpacePoint,
            NodeAggregateIdentifier::fromString($dataset['nodeAggregateIdentifier'] ?? $dataset['childNodeAggregateIdentifier'])
        );
    }

    private function findRelationAnchorPointByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): string {
        $nodeRecord = $this->dbalClient->getConnection()->executeQuery(
            'SELECT n.relationanchorpoint
                            FROM neos_contentgraph_node n
                            INNER JOIN neos_contentgraph_hierarchyrelation h
                            ON n.relationanchorpoint = h.childnodeanchor
                            WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                            AND h.contentstreamidentifier = :contentStreamIdentifier
                            AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier
            ]
        )->fetch();

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
