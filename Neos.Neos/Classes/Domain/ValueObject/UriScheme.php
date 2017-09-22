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

class UriScheme implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $uriScheme;

    /**
     * Name constructor.
     *
     * @param string $uriScheme
     */
    public function __construct(string $uriScheme)
    {
        $this->setUriScheme($uriScheme);
    }

    /**
     * @param string $uriScheme
     */
    protected function setUriScheme(string $uriScheme)
    {
        // TODO: BAD TO SUPPORT EMPTY SCHEME; but otherwise type conversion error (found together with Sebastian)
        if (!$uriScheme) {
            $this->uriScheme = null;
            return;
        }

        if (preg_match('/http|https/', $uriScheme) !== 1) {
            throw new \InvalidArgumentException('Invalid scheme.', 1505831235);
        }
        $this->uriScheme = $uriScheme;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->uriScheme;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->uriScheme;
    }
}
