<?php
namespace Neos\Neos\Domain\ValueObject;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class NodeType implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $type;

    /**
     * Name constructor.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->setType($type);
    }

    /**
     * @param string $type
     */
    protected function setType(string $type)
    {
        // TODO: add validation if needed
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->type;
    }
}
