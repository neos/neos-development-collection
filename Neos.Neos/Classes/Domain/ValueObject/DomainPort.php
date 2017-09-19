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

/**
 * Name of a workspace
 */
class DomainPort implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $port;

    /**
     * Name constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setPort($name);
    }

    /**
     * @param string $port
     */
    protected function setPort(string $port)
    {
        if (preg_match('/\d*/', $port) !== 1) {
            throw new \InvalidArgumentException('Invalid port.', 1505831415);
        }
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->port;
    }
}
