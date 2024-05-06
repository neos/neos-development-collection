<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * @internal
 */

#[Flow\Proxy(false)]
readonly class AssetIdAndOriginalAssetId
{
    public function __construct(
        public string $assetId,
        public ?string $originalAssetId,
    ) {
    }
}
