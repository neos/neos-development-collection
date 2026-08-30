<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage;

use Neos\Media\Domain\Model\AssetId;

interface ProvidesAssetIdsInterface
{
    /**
     * @return list<AssetId>
     */
    public function getAssetIds(): array;
}
