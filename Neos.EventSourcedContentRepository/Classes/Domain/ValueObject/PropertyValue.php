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
 * Property value with type
 * @Flow\Proxy(false)
 */
final class PropertyValue implements \JsonSerializable
{
    /**
     * @var mixed
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
        return new static($valueAndType['value'], $valueAndType['type']);
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
