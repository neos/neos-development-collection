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
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalProjectionIntegrityViolationDetectionRunnerFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap\Helpers\TestingNodeAggregateId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
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

    private function tableNames(): ContentGraphTableNames
    {
        return ContentGraphTableNames::create(
            $this->currentContentRepository->id
        );
    }

    public function setupDbalGraphAdapterIntegrityViolationTrait()
    {
        $this->dbalClient = $this->getObject(DoctrineDbalClient::class);
    }

    /**
     * @When /^I remove the following subtree tag:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     * @throws InvalidArgumentException
     */
    public function iRemoveTheFollowingSubtreeTag(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);
        $subtreeTagToRemove = SubtreeTag::fromString($dataset['subtreeTag']);
        $record = $this->transformDatasetToHierarchyRelationRecord($dataset);
        $subtreeTags = NodeFactory::extractNodeTagsFromJson($record['subtreetags']);
        if (!$subtreeTags->contain($subtreeTagToRemove)) {
            throw new \RuntimeException(sprintf('Failed to remove subtree tag "%s" because that tag is not set', $subtreeTagToRemove->value), 1708618267);
        }
        $this->dbalClient->getConnection()->update(
            $this->tableNames()->hierarchyRelation(),
            [
                'subtreetags' => json_encode($subtreeTags->without($subtreeTagToRemove), JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT),
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
            $this->tableNames()->hierarchyRelation(),
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
            $this->tableNames()->hierarchyRelation(),
            [
                'dimensionspacepointhash' => $dataset['newDimensionSpacePointHash']
            ],
            $record
        );
    }

    /**
     * @When /^I change the following hierarchy relation's name:$/
     * @param TableNode $payloadTable
     * @throws DBALException
     */
    public function iChangeTheFollowingHierarchyRelationsEdgeName(TableNode $payloadTable): void
    {
        $dataset = $this->transformPayloadTableToDataset($payloadTable);
        $record = $this->transformDatasetToHierarchyRelationRecord($dataset);
        unset($record['position']);

        $this->dbalClient->getConnection()->update(
            $this->tableNames()->hierarchyRelation(),
            [
                'name' => $dataset['newName']
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
            $this->tableNames()->hierarchyRelation(),
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
            $this->tableNames()->referenceRelation(),
            [
                'nodeanchorpoint' => 7777777
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
            $this->tableNames()->referenceRelation(),
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
            'nodeanchorpoint' => $this->findHierarchyRelationByIds(
                ContentStreamId::fromString($dataset['contentStreamId']),
                DimensionSpacePoint::fromArray($dataset['dimensionSpacePoint']),
                NodeAggregateId::fromString($dataset['sourceNodeAggregateId'])
            )['childnodeanchor'],
            'destinationnodeaggregateid' => $dataset['destinationNodeAggregateId']
        ];
    }

    private function transformDatasetToHierarchyRelationRecord(array $dataset): array
    {
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($dataset['dimensionSpacePoint']);
        $parentNodeAggregateId = TestingNodeAggregateId::fromString($dataset['parentNodeAggregateId']);
        $childAggregateId = TestingNodeAggregateId::fromString($dataset['childNodeAggregateId']);

        $parentHierarchyRelation = $parentNodeAggregateId->isNonExistent()
            ? null
            : $this->findHierarchyRelationByIds(
                ContentStreamId::fromString($dataset['contentStreamId']),
                $dimensionSpacePoint,
                NodeAggregateId::fromString($dataset['parentNodeAggregateId'])
            );

        $childHierarchyRelation = $childAggregateId->isNonExistent()
            ? null
            : $this->findHierarchyRelationByIds(
                ContentStreamId::fromString($dataset['contentStreamId']),
                $dimensionSpacePoint,
                NodeAggregateId::fromString($dataset['childNodeAggregateId'])
            );

        return [
            'contentstreamid' => $dataset['contentStreamId'],
            'dimensionspacepointhash' => $dimensionSpacePoint->hash,
            'parentnodeanchor' => $parentHierarchyRelation !== null ? $parentHierarchyRelation['childnodeanchor'] : 9999999,
            'childnodeanchor' => $childHierarchyRelation !== null ? $childHierarchyRelation['childnodeanchor'] : 8888888,
            'position' => $dataset['position'] ?? $parentHierarchyRelation !== null ? $parentHierarchyRelation['position'] : 0,
            'subtreetags' => $parentHierarchyRelation !== null ? $parentHierarchyRelation['subtreetags'] : '{}',
        ];
    }

    private function findRelationAnchorPointByDataset(array $dataset): int
    {
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($dataset['originDimensionSpacePoint'] ?? $dataset['dimensionSpacePoint']);

        return $this->findHierarchyRelationByIds(
            ContentStreamId::fromString($dataset['contentStreamId']),
            $dimensionSpacePoint,
            NodeAggregateId::fromString($dataset['nodeAggregateId'] ?? $dataset['childNodeAggregateId'])
        )['childnodeanchor'];
    }

    private function findHierarchyRelationByIds(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): array {
        $nodeRecord = $this->dbalClient->getConnection()->executeQuery(
            'SELECT h.*
                FROM ' . $this->tableNames()->node() . ' n
                INNER JOIN ' . $this->tableNames()->hierarchyRelation() . ' h
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
        if ($nodeRecord === false) {
            throw new \InvalidArgumentException(sprintf('Failed to find hierarchy relation for content stream "%s", dimension space point "%s" and node aggregate id "%s"', $contentStreamId->value, $dimensionSpacePoint->hash, $nodeAggregateId->value), 1708617712);
        }

        return $nodeRecord;
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
