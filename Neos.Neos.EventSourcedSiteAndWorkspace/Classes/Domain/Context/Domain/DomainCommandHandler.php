<?php

namespace Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain;

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
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Command\ActivateDomain;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Command\AddDomain;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Command\DeactivateDomain;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Command\DeleteDomain;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Event\DomainWasAdded;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Event\DomainWasActivated;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Event\DomainWasDeactivated;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Event\DomainWasDeleted;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Exception\DomainAlreadyExists;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Exception\DomainDoesNotExist;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Exception\SiteDoesNotExist;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Projection\Domain\DomainFinder;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Projection\Site\SiteFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeName;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\ValueObject\SchemeHostPort;

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
        $this->validateConstraintSiteMustExist($command->getSiteNodeName());
        $this->validateConstraintDomainMustNotExist($command->getSchemeHostPort());

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getSchemeHostPort(),
            new DomainWasAdded(
                $command->getSiteNodeName(),
                $command->getSchemeHostPort()
            )
        );
    }

    /**
     * @param ActivateDomain $command
     */
    public function handleActivateDomain(ActivateDomain $command)
    {
        $this->validateConstraintDomainMustExist($command->getSchemeHostPort());

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getSchemeHostPort(),
            new DomainWasActivated(
                $command->getSchemeHostPort()
            )
        );
    }

    /**
     * @param DeactivateDomain $command
     */
    public function handleDeactivateDomain(DeactivateDomain $command)
    {
        $this->validateConstraintDomainMustExist($command->getSchemeHostPort());

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getSchemeHostPort(),
            new DomainWasDeactivated(
                $command->getSchemeHostPort()
            )
        );
    }

    /**
     * @param DeleteDomain $command
     */
    public function handleDeleteDomain(DeleteDomain $command)
    {
        $this->validateConstraintDomainMustExist($command->getSchemeHostPort());

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getSchemeHostPort(),
            new DomainWasDeleted(
                $command->getSchemeHostPort()
            )
        );
    }

    /**
     * @param NodeName $siteNodeName
     * @throws SiteDoesNotExist
     */
    private function validateConstraintSiteMustExist(NodeName $siteNodeName): void
    {
        $site = $this->siteFinder->findOneByNodeName($siteNodeName);
        if ($site === null) {
            throw new SiteDoesNotExist($siteNodeName, 1505992197238);
        }
    }

    /**
     * @param SchemeHostPort $schemeHostPort
     * @throws DomainDoesNotExist
     * @internal param ActivateDomain $command
     */
    private function validateConstraintDomainMustExist(SchemeHostPort $schemeHostPort): void
    {
        $domain = $this->domainFinder->findOneBySchemeHostAndPort($schemeHostPort);
        if ($domain === null) {
            throw new DomainDoesNotExist($schemeHostPort, 1505992197238);
        }
    }

    /**
     * @param SchemeHostPort $schemeHostPort
     * @throws DomainAlreadyExists
     * @internal param AddDomain $command
     */
    private function validateConstraintDomainMustNotExist(SchemeHostPort $schemeHostPort): void
    {
        $domain = $this->domainFinder->findOneBySchemeHostAndPort($schemeHostPort);
        if ($domain !== null) {
            throw new DomainAlreadyExists($schemeHostPort, 1505918961915);
        }
    }
}
