<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset\ValueObject;

use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Audio;
use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\Video;
use Neos\Utility\TypeHandling;

final readonly class SerializedAsset implements \JsonSerializable
{
    private function __construct(
        public string $identifier,
        public AssetType $type,
        public string $title,
        public string $copyrightNotice,
        public string $caption,
        public string $assetSourceIdentifier,
        public SerializedResource $resource,
    ) {}

    public static function fromAsset(Asset $asset): self
    {
        /** @var PersistentResource|null $resource */
        $resource = $asset->getResource();
        if ($resource === null) {
            throw new \InvalidArgumentException(sprintf('Failed to load resource for asset "%s"', $asset->getIdentifier()), 1645871592);
        }
        $type = match ($typeForValue = TypeHandling::getTypeForValue($asset)) {
            Image::class => AssetType::IMAGE,
            Audio::class => AssetType::AUDIO,
            Document::class => AssetType::DOCUMENT,
            Video::class => AssetType::VIDEO,
            default => throw new \InvalidArgumentException(sprintf('Invalid asset type "%s" for asset "%s"', $typeForValue, $asset->getIdentifier()), 1698584356)
        };
        return new self(
            $asset->getIdentifier(),
            $type,
            $asset->getTitle(),
            $asset->getCopyrightNotice(),
            $asset->getCaption(),
            $asset->getAssetSourceIdentifier(),
            SerializedResource::fromResource($resource),
        );
    }

    public static function fromJsonString(string $jsonString): self
    {
        try {
            /** @var array{identifier: string, type: string, title: string, copyrightNotice: string, caption: string, assetSourceIdentifier: string, resource: array{filename: string, collectionName: string, mediaType: string, sha1: string}} $data */
            $data = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(sprintf('Failed to JSON-decode "%s" for %s instance: %s', $jsonString, self::class, $e->getMessage()), 1646992457, $e);
        }
        return self::fromArray($data);
    }

    /**
     * @param array{identifier: string, type: string, title: string, copyrightNotice: string, caption: string, assetSourceIdentifier: string, resource: array{filename: string, collectionName: string, mediaType: string, sha1: string}} $array
     * @return static
     */
    public static function fromArray(array $array): self
    {
        $expectedKeys = ['identifier', 'type', 'title', 'copyrightNotice', 'caption', 'assetSourceIdentifier', 'resource'];
        $missingKeys = array_diff($expectedKeys, array_keys($array));
        if ($missingKeys !== []) {
            throw new \InvalidArgumentException(sprintf('The following key%s missing: %s', count($missingKeys) === 1 ? ' is' : 's are', implode(', ', $missingKeys)), 1645872390);
        }
        $unknownKeys = array_diff(array_keys($array), $expectedKeys);
        if ($unknownKeys !== []) {
            throw new \InvalidArgumentException(sprintf('The following key%s unknown: %s', count($unknownKeys) === 1 ? ' is' : 's are', implode(', ', $unknownKeys)), 1645872665);
        }
        return new self(
            $array['identifier'],
            AssetType::from($array['type']),
            $array['title'],
            $array['copyrightNotice'],
            $array['caption'],
            $array['assetSourceIdentifier'],
            SerializedResource::fromArray($array['resource']),
        );
    }

    public function matches(Asset $asset): bool
    {
        /** @var PersistentResource|null $resource */
        $resource = $asset->getResource();
        if ($resource === null) {
            return false;
        }
        $matchesType = match($this->type) {
            AssetType::IMAGE => $asset instanceof ImageInterface,
            AssetType::AUDIO => $asset instanceof Audio,
            AssetType::DOCUMENT => $asset instanceof Document,
            AssetType::VIDEO => $asset instanceof Video,
        };
        if (!$matchesType) {
            return false;
        }

        return $asset->getIdentifier() === $this->identifier
            && $asset->getTitle() === $this->title
            && $asset->getCopyRightNotice() === $this->copyrightNotice
            && $asset->getCaption() === $this->caption
            && $asset->getAssetSourceIdentifier() === $this->assetSourceIdentifier
            && $this->resource->matches($resource);
    }

    public function toJson(): string
    {
        try {
            return json_encode($this, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-encode instance of %s: %s', self::class, $e->getMessage()), 1646314000, $e);
        }
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
