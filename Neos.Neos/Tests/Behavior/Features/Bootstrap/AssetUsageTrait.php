<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\AssetUsage\AssetUsageIndexingProcessor;
use Neos\Neos\AssetUsage\AssetUsageService;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use PHPUnit\Framework\Assert;

/**
 * Step implementations for tests inside Neos.Neos
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait AssetUsageTrait
{
    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @BeforeScenario
     */
    final public function pruneAssetUsage(): void
    {
        foreach (static::$alreadySetUpContentRepositories as $contentRepositoryId) {
            $this->getObject(\Neos\Neos\AssetUsage\Domain\AssetUsageRepository::class)->removeAll($contentRepositoryId);
        }
    }

    /**
     * @Then I expect the AssetUsageService to have the following AssetUsages:
     */
    public function iExpectTheAssetUsageServiceToHaveTheFollowingAssetUsages(TableNode $table)
    {
        $assetUsageService = $this->getObject(AssetUsageService::class);
        $assetUsages = iterator_to_array($assetUsageService->findByFilter($this->currentContentRepository->id, AssetUsageFilter::create()));

        $tableRows = $table->getHash();
        foreach ($assetUsages as $assetUsage) {
            foreach ($tableRows as $tableRowIndex => $tableRow) {
                if ($assetUsage->assetId !== $tableRow['assetId']
                    || $assetUsage->propertyName !== $tableRow['propertyName']
                    || !$assetUsage->workspaceName->equals(WorkspaceName::fromString($tableRow['workspaceName']))
                    || !$assetUsage->nodeAggregateId->equals(NodeAggregateId::fromString($tableRow['nodeAggregateId']))
                    || !$assetUsage->originDimensionSpacePoint->equals(DimensionSpacePoint::fromJsonString($tableRow['originDimensionSpacePoint']))
                ) {
                    continue;
                }
                unset($tableRows[$tableRowIndex]);
                continue 2;
            }
        }

        Assert::assertTrue(
            $tableRows === [] && count($assetUsages) === count($table->getHash()),
            sprintf('Mismatch between all actual asset usages %s and leftover asset usages to match %s', json_encode($assetUsages, JSON_PRETTY_PRINT), json_encode($tableRows, JSON_PRETTY_PRINT))
        );
    }

    /**
     * @When I run the AssetUsageIndexingProcessor with rootNodeTypeName ":rootNodeTypeName"
     */
    public function iRunTheAssetUsageIndexingProcessor(string $rootNodeTypeName)
    {
        $this->getObject(AssetUsageIndexingProcessor::class)->buildIndex(
            $this->currentContentRepository,
            NodeTypeName::fromString($rootNodeTypeName),
        );
    }
}
