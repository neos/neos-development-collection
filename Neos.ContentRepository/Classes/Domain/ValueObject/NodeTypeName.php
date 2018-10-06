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

final class NodeTypeName implements \JsonSerializable
{

    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Node type name must not be empty.', 1505835958);
        }

        $this->name = $name;
    }

    public function jsonSerialize()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->name;
    }
}
