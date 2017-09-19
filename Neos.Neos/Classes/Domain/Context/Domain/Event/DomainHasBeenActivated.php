<?php
namespace Neos\Neos\Domain\Context\Domain\Event;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


class DomainHasBeenActivated implements \Neos\EventSourcing\Event\EventInterface
{
    /**
     * @var \Neos\Neos\Domain\ValueObject\HostName
     */
    private $hostName;

    /**
     * ActivateDomain constructor.
     * @param \Neos\Neos\Domain\ValueObject\HostName $hostName
     */
    public function __construct(\Neos\Neos\Domain\ValueObject\HostName $hostName)
    {
        $this->hostName = $hostName;
    }

    /**
     * @return \Neos\Neos\Domain\ValueObject\HostName
     */
    public function getHostName(): \Neos\Neos\Domain\ValueObject\HostName
    {
        return $this->hostName;
    }

    /**
     * @param \Neos\Neos\Domain\ValueObject\HostName $hostName
     */
    public function setHostName(\Neos\Neos\Domain\ValueObject\HostName $hostName)
    {
        $this->hostName = $hostName;
    }
}
