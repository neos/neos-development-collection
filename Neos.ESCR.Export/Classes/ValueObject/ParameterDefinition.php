<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class ParameterDefinition
{

    private function __construct(
        public readonly string $name,
        public readonly string|int|bool|null $defaultValue
    ) {}

    public static function fromNameAndDefaultValue(string $name, string|int|bool|null $defaultValue): self
    {
        return new self($name, $defaultValue);
    }

    public function isRequired(): bool
    {
        return $this->defaultValue === null;
    }

    public function defaultValueHint(): string
    {
        return $this->defaultValue !== null ? ' [' . $this->defaultValue . ']' : '';
    }
}
