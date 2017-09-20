<?php

namespace Neos\Neos\Domain\Context\Site;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\EventPublisher;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Context\Domain\Command\ActivateDomain;
use Neos\Neos\Domain\Context\Domain\Command\AddDomain;
use Neos\Neos\Domain\Context\Domain\Command\DeactivateDomain;
use Neos\Neos\Domain\Context\Domain\Command\DeleteDomain;
use Neos\Neos\Domain\Context\Domain\Event\DomainWasActivated;
use Neos\Neos\Domain\Context\Domain\Event\DomainWasAdded;
use Neos\Neos\Domain\Context\Domain\Event\DomainWasDeactivated;
use Neos\Neos\Domain\Context\Domain\Event\DomainWasDeleted;
use Neos\Neos\Domain\Context\Domain\Exception\DomainAlreadyExists;
use Neos\Neos\Domain\Projection\Domain\DomainFinder;

/**
 * WorkspaceCommandHandler
 */
final class SiteCommandHandler
{
    /**
     * @Flow\Inject
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @var DomainFinder
     * @Flow\Inject
     */
    protected $domainFinder;

    /**
     * @param AddDomain $command
     */
    public function handleCreateSite(CreateSite $command)
    {
        $hostname = $command->getDomainHostname();
        $domain = $this->domainFinder->findOneByHostname($hostname);
        if ($domain !== null) {
            throw new DomainAlreadyExists($hostname, 1505918961915);
        }
        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $hostname,
            new DomainWasAdded(
                $command->getSiteNodeName(),
                $hostname,
                $command->getScheme(),
                $command->getPort()
            )
        );
    }

    /**
     * @param ActivateDomain $command
     */
    public function handleActivateDomain(ActivateDomain $command)
    {
        // TODO: Necessary checks

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getHostName(),
            new DomainWasActivated(
                $command->getHostName()
            )
        );
    }

    /**
     * @param DeactivateDomain $command
     */
    public function handleDeactivateDomain(DeactivateDomain $command)
    {
        // TODO: Necessary checks

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getHostName(),
            new DomainWasDeactivated(
                $command->getHostName()
            )
        );
    }

    /**
     * @param DeleteDomain $command
     */
    public function handleDeleteDomain(DeleteDomain $command)
    {
        // TODO: Necessary checks

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getHostName(),
            new DomainWasDeleted(
                $command->getHostName()
            )
        );
    }
}
