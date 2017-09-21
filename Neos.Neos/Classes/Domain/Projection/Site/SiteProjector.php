<?php
namespace Neos\Neos\Domain\Projection\Site;

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
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Context\Domain\Event\DomainWasAdded;
use Neos\Neos\Domain\Context\Site\Event\SiteWasActivated;
use Neos\Neos\Domain\Context\Site\Event\SiteWasCreated;
use Neos\Neos\Domain\Context\Site\Event\SiteWasDeactivated;
use Neos\Neos\Domain\Projection\Domain\Domain;

/**
 * Site Projector
 */
final class SiteProjector extends AbstractDoctrineProjector
{

    /**
     * @var SiteFinder
     * @Flow\Inject
     */
    protected $siteFinder;

    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @param DomainWasAdded $event
     */
    public function whenDomainWasAdded(DomainWasAdded $event)
    {
        // if site does not exist we are going to silently fail
        $site = $this->siteFinder->findOneByNodeName($event->getSiteNodeName());
        if ($site === null) {
            return;
        }

        $domain = new Domain();
        $domain->site = $site;
        $domain->hostName = (string)$event->getDomainHostName();
        $domain->domainPort = (string)$event->getPort() === '' ? null :(integer)(string)$event->getPort();
        $domain->uriScheme = (string)$event->getScheme() === '' ? null: (string)$event->getScheme();
        $domain->active = true;

        $this->add($domain);
    }

    /**
     * @param SiteWasCreated $event
     */
    public function whenSiteWasCreated(SiteWasCreated $event)
    {
        $site = $this->siteFinder->findOneByNodeName($event->getNodeName());
        if ($site !== null) {
            return;
        }

        $site = new Site();
        $site->name = $event->getSiteName();
        $site->nodeName = $event->getNodeName();
        $site->active = $event->getSiteActive();
        $site->siteResourcesPackageKey = $event->getSiteResourcesPackageKey();

        $this->add($site);
    }

    /**
     * @param SiteWasActivated $event
     */
    public function whenSiteWasActivated(SiteWasActivated $event)
    {
        $site = $this->siteFinder->findOneByNodeName($event->getNodeName());
        $site->active = true;

        $this->update($site);
    }

    /**
     * @param SiteWasDeactivated $event
     */
    public function whenSiteWasDeactivated(SiteWasDeactivated $event)
    {
        $site = $this->siteFinder->findOneByNodeName($event->getNodeName());
        $site->active = false;

        $this->update($site);
    }

}
