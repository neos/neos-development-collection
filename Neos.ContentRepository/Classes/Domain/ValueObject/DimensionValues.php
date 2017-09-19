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

final class DimensionValues implements \JsonSerializable
{

    /**
     * From dimension name to set of values (no fallbacks)
     *
     * @var array
     */
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    function jsonSerialize()
    {
        return [
            'values' => $this->values
        ];
    }

    public function __toString()
    {
        return 'dimension values:' . json_encode($this->values);
    }

}