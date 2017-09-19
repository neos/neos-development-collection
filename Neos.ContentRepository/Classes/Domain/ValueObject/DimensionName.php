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
use Neos\Flow\Annotations as Flow;

final class DimensionName implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $name;


    public function __construct(string $name)
    {
        $this->name = $name;
    }


    public function __toString(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
