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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Projection\AssetUsageFinder;
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
     * @Then I expect the AssetUsageProjection to have the following AssetUsages:
     */
    public function iExpectTheAssetUsageProjectionToHaveTheFollowingAssetUsages(TableNode $table)
    {
        $assetUsageFinder = $this->currentContentRepository->projectionState(AssetUsageFinder::class);
        $assetUsages = $assetUsageFinder->findByFilter(AssetUsageFilter::create());

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

        Assert::assertEmpty($tableRows, "Not all given asset usages where found.");
        Assert::assertSame($assetUsages->count(), count($table->getHash()), "More asset usages found as given.");

    }
}