<?php

namespace Neos\ContentRepository\Core\SharedModel;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

interface ContentRepositoryNodeInformationInterface
{
    public function getContentStreamId(): ContentStreamId;

    public function getOriginDimensionSpacePointHash(): string;

    public function getNodeAggregateId(): NodeAggregateId;
}