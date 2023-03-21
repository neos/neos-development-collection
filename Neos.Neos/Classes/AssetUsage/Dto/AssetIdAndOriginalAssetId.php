<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 * @internal
 */
class AssetIdAndOriginalAssetId
{
    public function __construct(
        public readonly string $assetId,
        public readonly ?string $originalAssetId,
    ) {
    }
}
