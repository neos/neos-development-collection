<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);


use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\Neos\Domain\Service\NodeDuplication\NodeAggregateIdMapping;
use Neos\Neos\Domain\Service\NodeDuplicationService;

/**
 * The node copying trait for behavioral tests
 */
trait NodeDuplicationTrait
{
    use CRTestSuiteRuntimeVariables;
    use ExceptionsTrait;

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @When /^copy nodes recursively is executed with payload:$/
     */
    public function copyNodesRecursivelyIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;

        $sourceNodeAggregateId = NodeAggregateId::fromString($commandArguments['sourceNodeAggregateId']);
        $sourceDimensionSpacePoint = isset($commandArguments['sourceDimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['sourceDimensionSpacePoint'])
            : $this->currentDimensionSpacePoint;

        $targetDimensionSpacePoint = isset($commandArguments['targetDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['targetDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->currentDimensionSpacePoint);

        $targetSucceedingSiblingNodeAggregateId = isset($commandArguments['targetSucceedingSiblingNodeAggregateId'])
            ? NodeAggregateId::fromString($commandArguments['targetSucceedingSiblingNodeAggregateId'])
            : null;

        $this->tryCatchingExceptions(
            fn () => $this->getObject(NodeDuplicationService::class)->copyNodesRecursively(
                $this->currentContentRepository->id,
                $workspaceName,
                $sourceDimensionSpacePoint,
                $sourceNodeAggregateId,
                $targetDimensionSpacePoint,
                NodeAggregateId::fromString($commandArguments['targetParentNodeAggregateId']),
                $targetSucceedingSiblingNodeAggregateId,
                NodeAggregateIdMapping::fromArray($commandArguments['nodeAggregateIdMapping'] ?? [])
            )
        );
    }

    /**
     * @When dimension variants are created recursively in :contentRepositoryId content repository with payload:
     */
    public function dimensionVariantsAreCreatedRecursivelyInContentRepositoryWithPayload(string $contentRepositoryId, TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;

        $nodeAggregateId = isset($commandArguments['nodeAggregateId'])
            ? NodeAggregateId::fromString($commandArguments['nodeAggregateId'])
            : null;
    
        $contentRepository = $this->getContentRepository(ContentRepositoryId::fromString($contentRepositoryId));

        $sourceDimensionSpacePoint = isset($commandArguments['sourceDimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['sourceDimensionSpacePoint'])
            : $this->currentDimensionSpacePoint;

        $sourceSubgraph = $contentRepository->getContentSubgraph($workspaceName, $sourceDimensionSpacePoint);

        $targetDimensionSpacePoint = DimensionSpacePoint::fromArray($commandArguments['targetDimensionSpacePoint']);

        $targetSubgraph = $contentRepository->getContentSubgraph($workspaceName, $targetDimensionSpacePoint);


        $this->tryCatchingExceptions(
            fn () => $this->getObject(NodeDuplicationService::class)->adoptNodeAndParents(
                $workspaceName,
                $nodeAggregateId,
                $sourceSubgraph,
                $targetSubgraph,
                $targetDimensionSpacePoint,
                $contentRepository,
                (bool)$commandArguments['withContent']
            )
        );
    }
}
