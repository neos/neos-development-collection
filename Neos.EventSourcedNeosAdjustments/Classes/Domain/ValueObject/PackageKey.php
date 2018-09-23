<?php
namespace Neos\EventSourcedNeosAdjustments\Domain\ValueObject;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class PackageKey implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $key;

    /**
     * Name constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->setKey($key);
    }

    /**
     * @param string $key
     */
    protected function setKey(string $key)
    {
        // TODO: add validation if needed
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->key;
    }
}
