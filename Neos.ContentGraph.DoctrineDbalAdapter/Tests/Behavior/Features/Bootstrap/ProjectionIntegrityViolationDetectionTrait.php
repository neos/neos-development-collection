<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap;

use Behat\Gherkin\Node\TableNode;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjectionFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalProjectionIntegrityViolationDetectionRunnerFactory;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap\Helpers\TestingNodeAggregateId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\ContentRepositoryRegistry\DoctrineDbalClient\DoctrineDbalClient;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use PHPUnit\Framework\Assert;

/**
 * Custom context trait for projection integrity violation detection specific to the Doctrine DBAL content graph adapter
 *
 * @todo move this class somewhere where its autoloaded
 */
trait ProjectionIntegrityViolationDetectionTrait
{
    use CRTestSuiteRuntimeVariables;

    private DoctrineDbalClient $dbalClient;

    protected Result $lastIntegrityViolationDetectionResult;

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    protected function getTableNamePrefix(): string
    {
        return DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix(
            $this->currentContentRepository->id
        );
    }

    public function setupDbalGraphAdapterIntegrityViolationTrait()
    {
        $this->dbalClient = $this->getObject(DoctrineDbalClient::class);
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
            'dimensionspacepoint' => $dimensionSpacePoint->toJson(),
            'dimensionspacepointhash' => $dimensionSpacePoint->hash,
            'parentnodeanchor' => $parentNodeAggregateId->isNonExistent()
                ? UuidFactory::create()
                : $this->findRelationAnchorPointByIds(
                    ContentStreamId::fromString($dataset['contentStreamId']),
                    $dimensionSpacePoint,
                    NodeAggregateId::fromString($dataset['parentNodeAggregateId'])
                ),
            'childnodeanchor' => $childAggregateId->isNonExistent()
                ? UuidFactory::create()
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
                'contentStreamId' => $contentStreamId->value,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateId' => $nodeAggregateId->value
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

    /**
     * @When /^I run integrity violation detection$/
     */
    public function iRunIntegrityViolationDetection(): void
    {
        $projectionIntegrityViolationDetectionRunner = $this->getContentRepositoryService(new DoctrineDbalProjectionIntegrityViolationDetectionRunnerFactory($this->dbalClient));
        $this->lastIntegrityViolationDetectionResult = $projectionIntegrityViolationDetectionRunner->run();
    }

    /**
     * @Then /^I expect the integrity violation detection result to contain exactly (\d+) errors?$/
     * @param int $expectedNumberOfErrors
     */
    public function iExpectTheIntegrityViolationDetectionResultToContainExactlyNErrors(int $expectedNumberOfErrors): void
    {
        Assert::assertCount(
            $expectedNumberOfErrors,
            $this->lastIntegrityViolationDetectionResult->getErrors(),
            'Errors were: ' . implode(', ', array_map(fn (Error $e) => $e->render(), $this->lastIntegrityViolationDetectionResult->getErrors()))
        );
    }

    /**
     * @Then /^I expect integrity violation detection result error number (\d+) to have code (\d+)$/
     * @param int $errorNumber
     * @param int $expectedErrorCode
     */
    public function iExpectIntegrityViolationDetectionResultErrorNumberNToHaveCodeX(int $errorNumber, int $expectedErrorCode): void
    {
        /** @var Error $error */
        $error = $this->lastIntegrityViolationDetectionResult->getErrors()[$errorNumber-1];
        Assert::assertSame(
            $expectedErrorCode,
            $error->getCode()
        );
    }
}
