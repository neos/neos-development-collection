<?php
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
     * @return BrowserConstraints
     */
    public static function fromJson(string $json): BrowserConstraints
    {
        $array = json_decode($json, true);

        return static::fromArray($array);
    }

    /**
     * @param array $array Keys: "allowedAssetSourceIdentifiers", "typeFilter"
     * @return BrowserConstraints
     */
    public static function fromArray(array $array): BrowserConstraints
    {
        $arguments[0] = [];
        if (isset($array['allowedAssetSourceIdentifiers']) && is_array($array['allowedAssetSourceIdentifiers'])) {
            $arguments[0] = $array['allowedAssetSourceIdentifiers'];
        }

        $arguments[1] = new AssetTypeFilter('All');
        if (isset($array['typeFilter'])) {
            $arguments[1] = new AssetTypeFilter((string)$array['typeFilter']);
        }

        return new BrowserConstraints(...$arguments);
    }

    /**
     * @param array $allowedAssetSourceIdentifiers
     * @return BrowserConstraints
     */
    public function withAssetSourceConstraint(array $allowedAssetSourceIdentifiers): BrowserConstraints
    {
        $newInstance = clone $this;
        $newInstance->allowedAssetSourceIdentifiers = $allowedAssetSourceIdentifiers;

        return $newInstance;
    }

    /**
     * @return BrowserConstraints
     */
    public function withoutAssetSourceConstraint(): BrowserConstraints
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
     */
    public function withTypeFilter(AssetTypeFilter $typeFilter)
    {
        $this->typeFilter = $typeFilter;
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
