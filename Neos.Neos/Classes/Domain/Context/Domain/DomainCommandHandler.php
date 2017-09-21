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
use Neos\Neos\Domain\Context\Domain\Exception\SiteDoesNotExists;
use Neos\Neos\Domain\Projection\Domain\Domain;
use Neos\Neos\Domain\Projection\Domain\DomainFinder;
use Neos\Neos\Domain\Projection\Site\SiteFinder;

/**
 * WorkspaceCommandHandler
 */
final class DomainCommandHandler
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
     * @var SiteFinder
     * @Flow\Inject
     */
    protected $siteFinder;

    /**
     * @param AddDomain $command
     */
    public function handleAddDomain(AddDomain $command)
    {
        $hostname = $command->getDomainHostname();

        $this->validateSiteMustExistsConstraint($command);
        $this->validateDomainMustNotExistConstraint($command);

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

    /**
     * @param AddDomain $command
     * @throws SiteDoesNotExists
     */
    private function validateSiteMustExistsConstraint(AddDomain $command): void
    {
        $site = $this->siteFinder->findOneByNodeName($command->getSiteNodeName());
        if ($site === null) {
            throw new SiteDoesNotExists($command->getSiteNodeName(), 1505992197238);
        }
    }

    /**
     * @param AddDomain $command
     * @throws DomainAlreadyExists
     */
    private function validateDomainMustNotExistConstraint(AddDomain $command): void
    {
        $hostName = $command->getDomainHostname();
        $scheme = $command->getScheme();
        $port = $command->getPort();
        $domain = $this->domainFinder->findOneByHostNameSchemeAndPort(
            $hostName,
            $scheme,
            $port
        );
        if ($domain !== null) {
            throw new DomainAlreadyExists($hostName, 1505918961915);
        }
    }
}
