<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class PresetId
{

    private function __construct(
        private readonly string $id,
    ) {}

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function toString(): string
    {
        return $this->id;
    }
}
