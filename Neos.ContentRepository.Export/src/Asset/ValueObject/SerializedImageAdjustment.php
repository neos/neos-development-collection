<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset\ValueObject;

use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;
use Neos\Media\Domain\Model\Adjustment\QualityImageAdjustment;
use Neos\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use Neos\Utility\ObjectAccess;
use Neos\Utility\TypeHandling;

final class SerializedImageAdjustment implements \JsonSerializable
{
    /** @param array<string, mixed> $properties */
    private function __construct(
        public readonly ImageAdjustmentType $type,
        public readonly array $properties,
    ) {}

    public static function fromImageAdjustment(ImageAdjustmentInterface $adjustment): self
    {
        $type = match(TypeHandling::getTypeForValue($adjustment)) {
            ResizeImageAdjustment::class => ImageAdjustmentType::RESIZE_IMAGE,
            CropImageAdjustment::class => ImageAdjustmentType::CROP_IMAGE,
            QualityImageAdjustment::class => ImageAdjustmentType::QUALITY_IMAGE,
        };
        return new self($type, $type->convertProperties(ObjectAccess::getGettableProperties($adjustment)));
    }

    /**
     * @param array{type: string, properties: array<mixed>} $array
     * @return static
     */
    public static function fromArray(array $array): self
    {
        $expectedKeys = ['type', 'properties'];
        $missingKeys = array_diff($expectedKeys, array_keys($array));
        if ($missingKeys !== []) {
            throw new \InvalidArgumentException(sprintf('The following key%s missing: %s', count($missingKeys) === 1 ? ' is' : 's are', implode(', ', $missingKeys)), 1648111790);
        }
        $unknownKeys = array_diff(array_keys($array), $expectedKeys);
        if ($unknownKeys !== []) {
            throw new \InvalidArgumentException(sprintf('The following key%s unknown: %s', count($unknownKeys) === 1 ? ' is' : 's are', implode(', ', $unknownKeys)), 1648111792);
        }
        return new self(ImageAdjustmentType::from($array['type']), $array['properties']);
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
