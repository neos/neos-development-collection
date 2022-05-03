<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Asset\ValueObject;

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\TypeHandling;

/**
 * @Flow\Proxy(false)
 */
final class SerializedImageAdjustment implements \JsonSerializable
{
    /** @param array<string, mixed> $properties */
    private function __construct(
        public readonly string $type,
        public readonly array $properties,
    ) {}

    public static function fromImageAdjustment(ImageAdjustmentInterface $adjustment): self
    {
        return new self(TypeHandling::getTypeForValue($adjustment), ObjectAccess::getGettableProperties($adjustment));
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
        return new self($array['type'], $array['properties']);
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
