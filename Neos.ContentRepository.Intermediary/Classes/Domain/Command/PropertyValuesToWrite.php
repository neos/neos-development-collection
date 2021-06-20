<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\Flow\Annotations as Flow;

/**
 * Property values to write, supports arbitrary objects. Will be then converted to {@see SerializedPropertyValues}
 * inside the events and persisted commands.
 *
 * This object does not store the types of the values separately, while in {@see SerializedPropertyValues}, the types
 * are stored in the data structure.
 * We expect the value types to match the NodeType's property types (this is validated in the command handler).
 *
 * @Flow\Proxy(false)
 */
final class PropertyValuesToWrite
{
    /**
     * @var array|mixed[]
     */
    private array $values = [];

    private function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    /**
     * @throws \JsonException
     */
    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
    }

    public function withValue(string $valueName, $value): self
    {
        $values = $this->values;
        $values[$valueName] = $value;

        return new self($values);
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->values, $other->getValues()));
    }

    /**
     * @return array|mixed[]
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
