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

use Neos\EventSourcing\Event\EventInterface;
use Neos\Neos\Domain\ValueObject\HostName;


class DomainHasBeenActivated implements EventInterface
{
    /**
     * @var HostName
     */
    private $hostName;

    /**
     * ActivateDomain constructor.
     * @param HostName $hostName
     */
    public function __construct(HostName $hostName)
    {
        $this->hostName = $hostName;
    }

    /**
     * @return HostName
     */
    public function getHostName(): HostName
    {
        return $this->hostName;
    }
}
