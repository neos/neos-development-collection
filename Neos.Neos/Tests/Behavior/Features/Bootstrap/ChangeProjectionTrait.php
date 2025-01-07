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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Neos\PendingChangesProjection\ChangeFinder;
use PHPUnit\Framework\Assert;

/**
 * Step implementations for tests inside Neos.Neos
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait ChangeProjectionTrait
{
    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @Then I expect the ChangeProjection to have the following changes in :contentStreamId:
     */
    public function iExpectTheChangeProjectionToHaveTheFollowingChangesInContentStream(TableNode $table, string $contentStreamId)
    {
        $changeFinder = $this->currentContentRepository->projectionState(ChangeFinder::class);
        $changes = iterator_to_array($changeFinder->findByContentStreamId(ContentStreamId::fromString($contentStreamId)));

        $tableRows = $table->getHash();
        foreach ($changes as $change) {
            foreach ($tableRows as $tableRowIndex => $tableRow) {
                if (!$change->nodeAggregateId->equals(NodeAggregateId::fromString($tableRow['nodeAggregateId']))
                    || $change->created !== (bool)$tableRow['created']
                    || $change->deleted !== (bool)$tableRow['deleted']
                    || $change->changed !== (bool)$tableRow['changed']
                    || $change->moved !== (bool)$tableRow['moved']
                    || !$change->originDimensionSpacePoint->equals(DimensionSpacePoint::fromJsonString($tableRow['originDimensionSpacePoint']))
                ) {
                    continue;
                }
                unset($tableRows[$tableRowIndex]);
                continue 2;
            }
        }


        Assert::assertTrue(
            $tableRows === [] && count($table->getHash()) === count($changes),
            sprintf('Mismatch between all actual changes usages %s and leftover changes to match %s', json_encode($changes, JSON_PRETTY_PRINT), json_encode($tableRows, JSON_PRETTY_PRINT))
        );
    }

    /**
     * @Then I expect the ChangeProjection to have no changes in :contentStreamId
     */
    public function iExpectTheChangeProjectionToHaveNoChangesInContentStream(string $contentStreamId)
    {
        $changeFinder = $this->currentContentRepository->projectionState(ChangeFinder::class);
        $changes = iterator_to_array($changeFinder->findByContentStreamId(ContentStreamId::fromString($contentStreamId)));

        Assert::assertEmpty($changes, "No changes expected, got: " . json_encode($changes, JSON_PRETTY_PRINT));
    }
}
