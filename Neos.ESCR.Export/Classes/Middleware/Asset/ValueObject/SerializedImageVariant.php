<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Asset\ValueObject;

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ImageVariant;

/**
 * @Flow\Proxy(false)
 */
final class SerializedImageVariant implements \JsonSerializable
{
    private function __construct(
        public readonly string $identifier,
        public readonly string $originalAssetIdentifier,
        public readonly string $name,
        public readonly int $width,
        public readonly int $height,
        public readonly ?string $presetIdentifier,
        public readonly ?string $presetVariantName,
        public readonly SerializedImageAdjustments $imageAdjustments,
    ) {}

    public static function fromImageVariant(ImageVariant $imageVariant): self
    {
        return new self(
            $imageVariant->getIdentifier(),
            $imageVariant->getOriginalAsset()->getIdentifier(),
            $imageVariant->getName(),
            $imageVariant->getWidth(),
            $imageVariant->getHeight(),
            $imageVariant->getPresetIdentifier(),
            $imageVariant->getPresetVariantName(),
            SerializedImageAdjustments::fromAdjustments($imageVariant->getAdjustments()),
        );
    }

    public static function fromJson(string $json): self
    {
        try {
            /** @var array{identifier: string, originalAssetIdentifier: string, name: string, width: int, height: int, presetIdentifier: ?string, presetVariantName: ?string, imageAdjustments: array<array{type: string, properties: array<mixed>}>} $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(sprintf('Failed to decode JSON: %s', $e->getMessage()), 1646992457);
        }
        return self::fromArray($data);
    }

    /**
     * @param array{identifier: string, originalAssetIdentifier: string, name: string, width: int, height: int, presetIdentifier: ?string, presetVariantName: ?string, imageAdjustments: array<array{type: string, properties: array<mixed>}>} $array
     * @return static
     */
    public static function fromArray(array $array): self
    {
        $expectedKeys = ['identifier', 'originalAssetIdentifier', 'name', 'width', 'height', 'presetIdentifier', 'presetVariantName', 'imageAdjustments'];
        $missingKeys = array_diff($expectedKeys, array_keys($array));
        if ($missingKeys !== []) {
            throw new \InvalidArgumentException(sprintf('The following key%s missing: %s', count($missingKeys) === 1 ? ' is' : 's are', implode(', ', $missingKeys)), 1648111496);
        }
        $unknownKeys = array_diff(array_keys($array), $expectedKeys);
        if ($unknownKeys !== []) {
            throw new \InvalidArgumentException(sprintf('The following key%s unknown: %s', count($unknownKeys) === 1 ? ' is' : 's are', implode(', ', $unknownKeys)), 1648111498);
        }
        return new self(
            $array['identifier'],
            $array['originalAssetIdentifier'],
            $array['name'],
            $array['width'],
            $array['height'],
            $array['presetIdentifier'],
            $array['presetVariantName'],
            SerializedImageAdjustments::fromArray($array['imageAdjustments']),
        );
    }

    public function matches(ImageVariant $imageVariant): bool
    {
        return self::fromImageVariant($imageVariant)->toJson() === $this->toJson();
    }

    public function toJson(): string
    {
        try {
            return json_encode($this, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON encode asset "%s": %s', $this->identifier, $e->getMessage()), 1646314000);
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
