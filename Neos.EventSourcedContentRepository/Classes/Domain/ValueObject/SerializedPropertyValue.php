<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * "Raw" / Serialized property value as saved in the event log // in projections.
 *
 * This means: "value" must be a simple PHP data type (no objects allowed!)
 *
 * @Flow\Proxy(false)
 */
final class SerializedPropertyValue implements \JsonSerializable
{
    /**
     * @var int|float|string|bool|array
     */
    private $value;

    /**
     * @var string
     */
    private $type;

    /**
     * @param mixed $value
     * @param string $type
     */
    public function __construct($value, string $type)
    {
        if (!is_scalar($value) || !is_array($value)) {
            // TODO: check that array does not contain nested objects...
            throw new \RuntimeException('TODO: Property value must not contain any objects.');
        }
        $this->value = $value;
        $this->type = $type;
    }

    public static function fromArray(array $valueAndType): self
    {
        if (!array_key_exists('value', $valueAndType)) {
            throw new \InvalidArgumentException('Missing array key "value"', 1546524597);
        }
        if (!array_key_exists('type', $valueAndType)) {
            throw new \InvalidArgumentException('Missing array key "type"', 1546524609);
        }

        $value = self::convertValueToObject($valueAndType['value'], $valueAndType['type']);

        return new static($value, $valueAndType['type']);
    }

    private static function convertValueToObject($value, $type)
    {
        if ($type === 'DateTime' && is_array($value) && isset($value['date']) && isset($value['timezone']) && isset($value['dateFormat'])) {
            return \DateTime::createFromFormat($value['dateFormat'], $value['date'], new \DateTimeZone($value['timezone']));
        }

        if (($type === 'DateTimeImmutable' || $type === 'DateTimeInterface') && is_array($value) && isset($value['date']) && isset($value['timezone']) && isset($value['dateFormat'])) {
            return \DateTimeImmutable::createFromFormat($value['dateFormat'], $value['date'], new \DateTimeZone($value['timezone']));
        }
        return $value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return [
            'value' => $this->value,
            'type' => $this->type
        ];
    }

    public function __toString()
    {
        return $this->value . ' (' . $this->type . ')';
    }
}
