<?php

namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
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

    function jsonSerialize()
    {
        return [
            'value' => $this->value,
            'type' => $this->type
        ];
    }

    public function __toString()
    {
        return $this->value . '(' . $this->type . ')';
    }

}