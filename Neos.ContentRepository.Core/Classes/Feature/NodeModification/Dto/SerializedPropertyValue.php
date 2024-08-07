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

use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;

/**
 * "Raw" / Serialized property value as saved in the event log // in projections.
 *
 * This means: "value" must be a simple PHP data type (no objects allowed!)
 * Null as value is not permitted! To unset a node property {@see SetSerializedNodeProperties::$propertiesToUnset} must be used.
 *
 * @phpstan-type Value int|float|string|bool|array<int|string,mixed>|\ArrayObject<int|string,mixed>
 *
 * @api used as part of commands/events
 */
final readonly class SerializedPropertyValue implements \JsonSerializable
{
    /**
     * @param Value $value
     */
    private function __construct(
        public int|float|string|bool|array|\ArrayObject $value,
        public string $type
    ) {
    }

    /**
     * If the value is NULL an unset-property instruction will be returned instead.
     *
     * @param Value $value
     */
    public static function create(
        int|float|string|bool|array|\ArrayObject $value,
        string $type
    ): self {
        return new self($value, $type);
    }

    /**
     * @param array{type:string,value:Value} $valueAndType
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

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        try {
            $valueAsJson = json_encode($this->value, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-encode %s: %s', self::class, $e->getMessage()), 1723032361, $e);
        }
        return [
            'type' => $this->type,
            'value' => $valueAsJson,
        ];
    }
}
