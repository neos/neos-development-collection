<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * @api
 */
#[Flow\Proxy(false)]
final readonly class AssetUsageFilter
{
    private function __construct(
        public ?string $assetId,
        public ?ContentStreamId $contentStreamId,
        public bool $groupByAsset,
        public bool $groupByNode,
        public bool $includeVariantsOfAsset,
    ) {
    }

    public static function create(): self
    {
        return new self(null, null, false, false, false);
    }

    public function withAsset(string $assetId): self
    {
        return new self($assetId, $this->contentStreamId, $this->groupByAsset, $this->groupByNode, $this->includeVariantsOfAsset);
    }

    public function withContentStream(ContentStreamId $contentStreamId): self
    {
        return new self($this->assetId, $contentStreamId, $this->groupByAsset, $this->groupByNode, $this->includeVariantsOfAsset);
    }

    public function includeVariantsOfAsset(): self
    {
        return new self($this->assetId, $this->contentStreamId, $this->groupByAsset, $this->groupByNode, true);
    }

    public function groupByAsset(): self
    {
        return new self($this->assetId, $this->contentStreamId, true, $this->groupByNode, $this->includeVariantsOfAsset);
    }

    public function groupByNode(): self
    {
        return new self($this->assetId, $this->contentStreamId, $this->groupByAsset, true, $this->includeVariantsOfAsset);
    }

    public function hasAssetId(): bool
    {
        return $this->assetId !== null;
    }

    public function hasContentStreamId(): bool
    {
        return $this->contentStreamId !== null;
    }
}
