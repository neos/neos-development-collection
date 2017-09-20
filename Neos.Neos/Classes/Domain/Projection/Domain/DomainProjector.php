<?php
namespace Neos\Neos\Domain\Projection\Domain;

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
use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineProjector;
use Neos\Neos\Domain\Context\Domain\Event\DomainWasAdded;

/**
 * Workspace Projector
 */
final class DomainProjector extends AbstractDoctrineProjector
{

    /**
     * @var \Neos\Neos\Domain\Repository\SiteRepository
     * @Flow\Inject
     */
    protected $siteRepository;

    /**
     * @var \Neos\Flow\Persistence\PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @param DomainWasAdded $event
     */
    public function whenDomainWasAdded(DomainWasAdded $event)
    {
        // TODO do we need to check if the site exists at this point ?
        $site = $this->siteRepository->findOneByNodeName($event->getSiteNodeName());

        $domain = new Domain();
        $domain->site = $this->persistenceManager->getIdentifierByObject($site);
        $domain->hostname = (string)$event->getDomainHostname();
        $domain->port = (string)$event->getPort() === '' ? null :(integer)(string)$event->getPort();
        $domain->scheme = (string)$event->getScheme() === '' ? null: (string)$event->getScheme();
        $domain->active = true;

        $this->add($domain);
    }
}
