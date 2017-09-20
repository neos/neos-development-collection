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

class SiteActive implements \JsonSerializable
{
    /**
     * @var bool
     */
    protected $isActive;

    /**
     * Name constructor.
     *
     * @param bool $isActive
     */
    public function __construct(bool $isActive)
    {
        $this->setIsActive($isActive);
    }

    /**
     * @param bool $isActive
     */
    protected function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->isActive;
    }
}
