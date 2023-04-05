<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * @internal
 */
#[Flow\Proxy(false)]
class AssetIdAndOriginalAssetId
{
    public function __construct(
        public readonly string $assetId,
        public readonly ?string $originalAssetId,
    ) {
    }
}
