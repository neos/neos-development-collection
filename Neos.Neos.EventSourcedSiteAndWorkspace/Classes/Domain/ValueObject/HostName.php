<?php
namespace Neos\Neos\EventSourcedSiteAndWorkspace\Domain\ValueObject;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class HostName implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $name;

    /**
     * Name constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * @param string $name
     */
    protected function setName(string $name)
    {
        // TODO: add validation if needed
//        if (preg_match('/^[\p{L}\p{P}\d \.]{2,255}$/u', $name) !== 1) {
//            throw new \InvalidArgumentException('Invalid workspace name given.', 1505826610318);
//        }
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->name;
    }
}
