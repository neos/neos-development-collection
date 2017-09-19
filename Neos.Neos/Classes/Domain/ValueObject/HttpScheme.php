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
class HttpScheme implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $scheme;

    /**
     * Name constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setScheme($name);
    }

    /**
     * @param string $scheme
     */
    protected function setScheme(string $scheme)
    {
        // TODO: BAD TO SUPPORT EMPTY SCHEME; but otherwise type conversion error (found together with Sebastian)
        if (!$scheme) {
            $this->scheme = null;
            return;
        }

        if (preg_match('/http|https/', $scheme) !== 1) {
            throw new \InvalidArgumentException('Invalid scheme.', 1505831235);
        }
        $this->scheme = $scheme;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->scheme;
    }
}
