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

class SiteActive implements \JsonSerializable
{
    /**
     * @var bool
     */
    protected $active;

    /**
     * Name constructor.
     *
     * @param bool $isActive
     */
    public function __construct(bool $isActive)
    {
        $this->setActive($isActive);
    }

    /**
     * @param bool $active
     */
    protected function setActive(bool $active)
    {
        // TODO: add validation if needed
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function jsonSerialize()
    {
        return $this->active;
    }
}
