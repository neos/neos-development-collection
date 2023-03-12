<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeModification\Dto;

/**
 * "Raw" / Serialized property value as saved in the event log // in projections.
 *
 * This means: "value" must be a simple PHP data type (no objects allowed!)
 *
 * @api used as part of commands/events
 */
final class SerializedPropertyValue implements \JsonSerializable
{
    /**
     * @param int|float|string|bool|array<int|string,mixed>|\ArrayObject<int|string,mixed>|null $value
     */
    public function __construct(
        public readonly int|float|string|bool|array|\ArrayObject|null $value,
        public readonly string $type
    ) {
    }

    /**
     * @param array<string,mixed> $valueAndType
     */
    public static function fromArray(array $valueAndType): self
    {
        if (!array_key_exists('value', $valueAndType)) {
            throw new \InvalidArgumentException('Missing array key "value"', 1546524597);
        }
        if (!array_key_exists('type', $valueAndType)) {
            throw new \InvalidArgumentException('Missing array key "type"', 1546524609);
        }

        return new self($valueAndType['value'], $valueAndType['type']);
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'type' => $this->type
        ];
    }

    public function __toString(): string
    {
        return json_encode($this->value, JSON_THROW_ON_ERROR) . ' (' . $this->type . ')';
    }
}
