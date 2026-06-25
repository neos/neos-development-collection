<?php

use PHPUnit\Framework\Assert;

/**
 * @internal custom non api inspections for the Doctrine DBAL content graph adapter to make assertions
 */
trait DoctrineDbalProjectionSpyTrait
{
    /**
     * @Then I expect :number hierarchies to exist in the active write layer
     */
    public function iExpectNumberHierarchiesToExistInTheActiveWriteLayer(int $number)
    {
        $contentGraph = $this->currentContentRepository->getContentGraph($this->currentWorkspaceName);

        $layers = $this->contentStreamLayerFinder()->getContentStreamLayers($contentGraph->getContentStreamId());

        $count = $this->dbal->fetchOne(<<<SQL
        SELECT COUNT(*) FROM {$this->tableNames()->hierarchyRelation()} WHERE contentStreamLayer = :contentStreamLayer
        SQL, ['contentStreamLayer' => $layers->getWriteLayer()->value]);

        Assert::assertEquals($number, $count);
    }
}
