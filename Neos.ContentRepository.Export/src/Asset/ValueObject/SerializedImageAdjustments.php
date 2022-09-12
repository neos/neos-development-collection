<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset\ValueObject;

use Neos\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;

/**
 * @implements \IteratorAggregate<SerializedImageAdjustment>
 */
final class SerializedImageAdjustments implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<SerializedImageAdjustment> $serializedAdjustments
     */
    private function __construct(
        private readonly array $serializedAdjustments,
    ) {}

    /**
     * @param \Traversable<ImageAdjustmentInterface> $adjustments
     * @return static
     */
    public static function fromAdjustments(\Traversable $adjustments): self
    {
        return new self(array_map(static fn(ImageAdjustmentInterface $adjustment) => SerializedImageAdjustment::fromImageAdjustment($adjustment), iterator_to_array($adjustments)));
    }

    /**
     * @param array<array{type: string, properties: array<mixed>}> $array
     * @return static
     */
    public static function fromArray(array $array): self
    {
        return new self(array_map(static fn(array $adjustment) => SerializedImageAdjustment::fromArray($adjustment), $array));
    }

    /**
     * @return \Traversable<SerializedImageAdjustment>|SerializedImageAdjustment[]
     * @noinspection PhpDocSignatureInspection
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->serializedAdjustments);
    }

    /**
     * @return array<SerializedImageAdjustment>
     */
    public function jsonSerialize(): array
    {
        return $this->serializedAdjustments;
    }
}
