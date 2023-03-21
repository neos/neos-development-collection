<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

class AssetIdAndOriginalAssetId
{
    public function __construct(
        public readonly string $assetId,
        public readonly ?string $originalAssetId,
    ) {
    }
}
