<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * @api
 */
#[Flow\Proxy(false)]
final readonly class AssetUsageFilter
{
    private function __construct(
        public ?string $assetId,
        public ?WorkspaceName $workspaceName,
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
        return new self($assetId, $this->workspaceName, $this->groupByAsset, $this->groupByNode, $this->includeVariantsOfAsset);
    }

    public function withWorkspaceName(WorkspaceName $workspaceName): self
    {
        return new self($this->assetId, $workspaceName, $this->groupByAsset, $this->groupByNode, $this->includeVariantsOfAsset);
    }

    public function includeVariantsOfAsset(): self
    {
        return new self($this->assetId, $this->workspaceName, $this->groupByAsset, $this->groupByNode, true);
    }

    public function groupByAsset(): self
    {
        return new self($this->assetId, $this->workspaceName, true, $this->groupByNode, $this->includeVariantsOfAsset);
    }

    public function groupByNode(): self
    {
        return new self($this->assetId, $this->workspaceName, $this->groupByAsset, true, $this->includeVariantsOfAsset);
    }

    public function hasAssetId(): bool
    {
        return $this->assetId !== null;
    }

    public function hasWorkspaceName(): bool
    {
        return $this->workspaceName !== null;
    }
}
