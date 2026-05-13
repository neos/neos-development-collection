<?php

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

trait ContentStreamLayerTrait
{
    private function getContentStreamLayers(ContentStreamId $contentStreamId): ContentStreamLayers
    {
        return ContentStreamLayers::fromArray(
            $this->dbal->executeQuery(
                'SELECT contentstreamlayer FROM ' . $this->tableNames()->contentStreamLayer() . ' WHERE contentstreamid = :contentStreamId',
                [
                    'contentStreamId' => $contentStreamId->value,
                ]
            )->fetchFirstColumn()
        );
    }
}
