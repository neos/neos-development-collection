<?php

namespace Neos\Neos\Domain\Context\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Context\Domain\Command\ActivateDomain;
use Neos\Neos\Domain\Context\Domain\Event\DomainHasBeenActivated;

class DomainCommandHandler
{

    /**
     * @Flow\Inject
     * @var \Neos\EventSourcing\Event\EventPublisher
     */
    protected $eventPublisher;

    /**
     * @param ActivateDomain $command
     */
    public function handleActivateDomain(ActivateDomain $command)
    {

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getHostName(),
            new DomainHasBeenActivated(
                $command->getHostName()
            )
        );
    }
}
