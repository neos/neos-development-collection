<?php
declare(strict_types=1);

namespace Neos\Media\Browser\Domain\Dto;

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetSource\AssetTypeFilter;

/**
 * @Flow\Proxy(false)
 * Media Browser constraints
 */
final class BrowserConstraints implements \JsonSerializable
{
    /**
     * @var string[]
     */
    private $allowedAssetSourceIdentifiers = [];

    /**
     * @var AssetTypeFilter
     */
    private $typeFilter;

    /**
     * Create media browser constraints
     *
     * @param array $allowedAssetSourceIdentifiers Empty array means all allowed!
     * @param AssetTypeFilter|null $typeFilter
     */
    private function __construct(array $allowedAssetSourceIdentifiers = [], AssetTypeFilter $typeFilter = null)
    {
        $this->allowedAssetSourceIdentifiers = $allowedAssetSourceIdentifiers;
        $this->typeFilter = $typeFilter;
    }

    /**
     * @param string $json
     * @return self
     */
    public static function fromJson(string $json): self
    {
        $array = json_decode($json, true);

        return static::fromArray($array);
    }

    /**
     * @param array $array Keys: "allowedAssetSourceIdentifiers", "typeFilter"
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $arguments[0] = [];
        if (isset($array['allowedAssetSourceIdentifiers']) && is_array($array['allowedAssetSourceIdentifiers'])) {
            $arguments[0] = $array['allowedAssetSourceIdentifiers'];
        }

        $arguments[1] = new AssetTypeFilter(AssetTypeFilter::TYPE_ALL);
        if (isset($array['typeFilter'])) {
            $arguments[1] = new AssetTypeFilter((string)$array['typeFilter']);
        }

        return new BrowserConstraints(...$arguments);
    }

    /**
     * @param array $allowedAssetSourceIdentifiers
     * @return self
     */
    public function withAssetSourceConstraint(array $allowedAssetSourceIdentifiers): self
    {
        $newInstance = clone $this;
        $newInstance->allowedAssetSourceIdentifiers = $allowedAssetSourceIdentifiers;

        return $newInstance;
    }

    /**
     * @return self
     */
    public function withoutAssetSourceConstraint(): self
    {
        $newInstance = clone $this;
        $newInstance->allowedAssetSourceIdentifiers = [];

        return $newInstance;
    }

    /**
     * @return string[]
     */
    public function getAllowedAssetSourceIdentifiers(): array
    {
        return $this->allowedAssetSourceIdentifiers;
    }

    /**
     * @return bool
     */
    public function hasAssetSourceConstraint(): bool
    {
        return $this->allowedAssetSourceIdentifiers !== [];
    }

    /**
     * @return AssetTypeFilter
     */
    public function getTypeFilter(): AssetTypeFilter
    {
        return $this->typeFilter;
    }

    /**
     * @param AssetTypeFilter $typeFilter
     * @return BrowserConstraints
     */
    public function withTypeFilter(AssetTypeFilter $typeFilter): self
    {
        $newInstance = clone $this;
        $newInstance->typeFilter = $typeFilter;

        return $newInstance;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = [];
        if ($this->hasAssetSourceConstraint()) {
            $data['allowedAssetSourceIdentifiers'] = $this->allowedAssetSourceIdentifiers;
        }

        $data['typeFilter'] = $this->typeFilter;

        return $data;
    }
}
