<?php
declare(strict_types=1);
namespace Neos\Media\Domain\Model\Dto;

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\AssetSource\AssetTypeFilter;

/**
 * Constraints for the Assets that can't be changed by the user while navigating the Media module / endpoints (other than filters)
 *
 * @Flow\Proxy(false)
 */
final class AssetConstraints
{
    private const PATTERN_MEDIA_TYPE = '/^(?P<type>(?:[\.!#%&\'\`\^~\$\*\+\-\|\w]+))\/(?P<subtype>(?:[\.!#%&\'\`\^~\$\*\+\-\|\w]+))$/i';

    /**
     * @var string[]
     */
    private $allowedAssetSourceIdentifiers;

    /**
     * @var string[]
     */
    private $allowedMediaTypes;

    /**
     * @internal
     * @var string[]
     */
    private $allowedAssetTypes;

    /**
     * @param string[] $allowedAssetSourceIdentifiers Empty array means all allowed!
     * @param string[] $allowedMediaTypes Empty array means all allowed!
     */
    private function __construct(array $allowedAssetSourceIdentifiers = [], array $allowedMediaTypes = [])
    {
        $this->allowedAssetSourceIdentifiers = $allowedAssetSourceIdentifiers;
        $this->allowedMediaTypes = $allowedMediaTypes;
        $this->allowedAssetTypes = array_unique(array_map(static function (string $mediaType) {
            if (preg_match(self::PATTERN_MEDIA_TYPE, $mediaType, $matches) === 0) {
                throw new \InvalidArgumentException(sprintf('Failed to parse media type "%s"', $mediaType), 1594727068);
            }
            $type = strtolower($matches['type']) ?? '';
            if (in_array($type, ['image', 'audio', 'video'], true)) {
                return ucfirst($type);
            }
            return 'Document';
        }, $this->allowedMediaTypes));
    }

    /**
     * Create an empty instance (without any active constraints)
     *
     * @return self
     */
    public static function create(): self
    {
        return new self([], []);
    }

    /**
     * @param array $array Keys: "assetSources", "mediaTypes"
     * @return self
     */
    public static function fromArray(array $array): self
    {
        if (isset($array['assetSources'])) {
            if (!is_array($array['assetSources'])) {
                throw new \InvalidArgumentException(sprintf('"assetSources" must be an array, given: %s', gettype($array['assetSources'])), 1594372054);
            }
            $allowedAssetSourceIdentifiers = $array['assetSources'];
            unset($array['assetSources']);
        }
        if (isset($array['mediaTypes'])) {
            if (!is_array($array['mediaTypes'])) {
                throw new \InvalidArgumentException(sprintf('"mediaTypes" must be a array, given: %s', gettype($array['mediaTypes'])), 1594372096);
            }
            $allowedMediaTypes = $array['mediaTypes'];
            unset($array['mediaTypes']);
        }
        if ($array !== []) {
            throw new \InvalidArgumentException(sprintf('Unsupported asset constraint(s): "%s"', implode('", "', array_keys($array))), 1594633851);
        }

        return new self($allowedAssetSourceIdentifiers ?? [], $allowedMediaTypes ?? []);
    }

    /**
     * @param array $allowedAssetSourceIdentifiers
     * @return self
     */
    public function withAssetSourceConstraint(array $allowedAssetSourceIdentifiers): self
    {
        return new self($allowedAssetSourceIdentifiers, $this->allowedMediaTypes);
    }

    /**
     * @return self
     */
    public function withoutAssetSourceConstraint(): self
    {
        return new self([], $this->allowedMediaTypes);
    }

    /**
     * @param string[] $allowedMediaType
     * @return self
     */
    public function withMediaTypeConstraint(array $allowedMediaType): self
    {
        return new self($this->allowedAssetSourceIdentifiers, $allowedMediaType);
    }

    /**
     * @return self
     */
    public function withoutAssetTypeConstraint(): self
    {
        return new self($this->allowedAssetSourceIdentifiers, []);
    }

    /**
     * @return bool
     */
    public function hasAssetSourceConstraint(): bool
    {
        return $this->allowedAssetSourceIdentifiers !== [];
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
    public function hasMediaTypeConstraint(): bool
    {
        return $this->allowedMediaTypes !== [];
    }

    /**
     * @return string[]
     */
    public function getAllowedMediaTypes(): array
    {
        return $this->allowedMediaTypes;
    }

    /**
     * Returns the allowed media types as a string that can be used for "accept" attributes in file upload HTML elements
     *
     * @return string comma separated list of allowed media types or an empty string if no media type constraints are active
     */
    public function getMediaTypeAcceptAttribute(): string
    {
        return implode(',', $this->allowedMediaTypes);
    }

    /**
     * Filters the given $assetSources according to the active asset source constraints
     * If no asset source constraints is active, the original array is returned
     *
     * @param AssetSourceInterface[] $assetSources
     * @return AssetSourceInterface[]
     */
    public function applyToAssetSources(array $assetSources): array
    {
        if (!$this->hasAssetSourceConstraint()) {
            return $assetSources;
        }
        return array_filter($assetSources, function (AssetSourceInterface $assetSource) {
            return in_array($assetSource->getIdentifier(), $this->allowedAssetSourceIdentifiers, true);
        });
    }

    /**
     * Verifies the given $assetSourceIdentifier against the asset source constraint:
     * If no asset source constraint is set or the given $assetSourceIdentifier matches one of the allowedAssetSourceIdentifiers, the input is un-altered
     * Otherwise the first allowed allowedAssetSourceIdentifier is returned
     *
     * @param string|null $assetSourceIdentifier
     * @return string|null
     */
    public function applyToAssetSourceIdentifiers(?string $assetSourceIdentifier): ?string
    {
        if (!$this->hasAssetSourceConstraint() || in_array($assetSourceIdentifier, $this->allowedAssetSourceIdentifiers, true)) {
            return $assetSourceIdentifier;
        }
        return $this->allowedAssetSourceIdentifiers[array_key_first($this->allowedAssetSourceIdentifiers)] ?? null;
    }

    /**
     * Verifies the given $assetType against the media type constraint:
     * If no media type constraint is set or the given $assetType matches one of the allowed asset types, the input is un-altered
     * Otherwise the first allowed asset type is returned
     *
     * @param string|null $assetType
     * @return AssetTypeFilter
     */
    public function applyToAssetTypeFilter(string $assetType = null): AssetTypeFilter
    {
        if (!$this->hasMediaTypeConstraint() || in_array($assetType, $this->allowedAssetTypes, true)) {
            return new AssetTypeFilter($assetType ?? 'All');
        }
        return new AssetTypeFilter($this->allowedAssetTypes[array_key_first($this->allowedAssetTypes)]);
    }

    /**
     * Returns an array with all supported asset type filter values according to the active media type constraints
     * If no media type constraint is set, all options are returned including the special "All": ("All", "Image", "Video", ...)
     * Otherwise only the allowed asset types are returned ("Video", "Image").
     * If only one asset type is allowed, an empty array is returned because a filter with only one option is useless
     *
     * @return array
     */
    public function getAllowedAssetTypeFilterOptions(): array
    {
        $allAllowedFilterValues = AssetTypeFilter::getAllowedValues();
        if (!$this->hasMediaTypeConstraint()) {
            return $allAllowedFilterValues;
        }
        $filteredValues = array_intersect($this->allowedAssetTypes, $allAllowedFilterValues);
        return count($filteredValues) > 1 ? $filteredValues : [];
    }
}
