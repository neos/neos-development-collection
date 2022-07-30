<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Command;

use Neos\ContentRepository\Feature\Common\Exception\NodeNameIsAlreadyOccupied;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Exception\SiteNodeNameIsAlreadyInUseByAnotherSite;
use Neos\Neos\Domain\Exception\SiteNodeTypeIsInvalid;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Domain\Model\Site;

/**
 * The Site Command Controller
 *
 * @Flow\Scope("singleton")
 */
class SiteCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var SiteService
     */
    protected $siteService;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Create a new site
     *
     * This command allows to create a blank site with just a single empty document in the default dimension.
     * The name of the site, the packageKey must be specified.
     *
     * The node type given with the ``nodeType`` option must already exists
     * and have the superType ``Neos.Neos:Document``.
     *
     * If no ``nodeName`` option is specified the command will create a unique node-name from the name of the site.
     * If a node name is given it has to be unique for the setup.
     *
     * If the flag ``activate`` is set to false new site will not be activated.
     *
     * @param string $name The name of the site
     * @param string $packageKey The site package
     * @param string $nodeType The node type to use for the site node, e.g. Amce.Com:Page
     * @param string $nodeName The name of the site node.
     *                         If no nodeName is given it will be determined from the siteName.
     * @param boolean $inactive The new site is not activated immediately (default = false)
     * @return void
     */
    public function createCommand($name, $packageKey, $nodeType, $nodeName = null, $inactive = false)
    {
        if ($this->packageManager->isPackageAvailable($packageKey) === false) {
            $this->outputLine('<error>Could not find package "%s"</error>', [$packageKey]);
            $this->quit(1);
        }

        try {
            $this->siteService->createSite($packageKey, $name, $nodeType, $nodeName, $inactive);
        } catch (NodeTypeNotFoundException $exception) {
            $this->outputLine('<error>The given node type "%s" was not found</error>', [$nodeType]);
            $this->quit(1);
        } catch (SiteNodeTypeIsInvalid $exception) {
            $this->outputLine(
                '<error>The given node type "%s" is not based on the superType "%s"</error>',
                [$nodeType, NodeTypeNameFactory::forSite()]
            );
            $this->quit(1);
        } catch (SiteNodeNameIsAlreadyInUseByAnotherSite | NodeNameIsAlreadyOccupied $exception) {
            $this->outputLine('<error>A site with siteNodeName "%s" already exists</error>', [$nodeName]);
            $this->quit(1);
        }

        $this->outputLine(
            'Successfully created site "%s" with siteNode "%s", type "%s", packageKey "%s" and state "%s"',
            [$name, $nodeName, $nodeType, $packageKey, $inactive ? 'offline' : 'online']
        );
    }

    /**
     * Remove site with content and related data (with globbing)
     *
     * In the future we need some more sophisticated cleanup.
     *
     * @param string $siteNode Name for site root nodes to clear only content of this sites (globbing is supported)
     * @return void
     */
    public function pruneCommand($siteNode)
    {
        $sites = $this->findSitesByNodeNamePattern($siteNode);
        if (empty($sites)) {
            $this->outputLine('<error>No Site found for pattern "%s".</error>', [$siteNode]);
            // Help the user a little about what he needs to provide as a parameter here
            $this->outputLine('To find out which sites you have, use the <b>site:list</b> command.');
            $this->outputLine('The site:prune command expects the "Node name" from the site list as a parameter.');
            $this->outputLine('If you want to delete all sites, you can run <b>site:prune \'*\'</b>.');
            $this->quit(1);
        }
        foreach ($sites as $site) {
            $this->siteService->pruneSite($site);
            $this->outputLine(
                'Site with root "%s" matched pattern "%s" and has been removed.',
                [$site->getNodeName(), $siteNode]
            );
        }
    }

    /**
     * List available sites
     *
     * @return void
     */
    public function listCommand()
    {
        $sites = $this->siteRepository->findAll();

        if ($sites->count() === 0) {
            $this->outputLine('No sites available');
            $this->quit(0);
        }

        $longestSiteName = 4;
        $longestNodeName = 9;
        $longestSiteResource = 17;
        $availableSites = [];

        foreach ($sites as $site) {
            /** @var Site $site */
            array_push($availableSites, [
                'name' => $site->getName(),
                'nodeName' => $site->getNodeName()->value,
                'siteResourcesPackageKey' => $site->getSiteResourcesPackageKey(),
                'status' => ($site->getState() === SITE::STATE_ONLINE) ? 'online' : 'offline'
            ]);
            if (strlen($site->getName()) > $longestSiteName) {
                $longestSiteName = strlen($site->getName());
            }
            if (strlen($site->getNodeName()->value) > $longestNodeName) {
                $longestNodeName = strlen($site->getNodeName()->value);
            }
            if (strlen($site->getSiteResourcesPackageKey()) > $longestSiteResource) {
                $longestSiteResource = strlen($site->getSiteResourcesPackageKey());
            }
        }

        $this->outputLine();
        $this->outputLine(' ' . str_pad('Name', $longestSiteName + 15)
            . str_pad('Node name', $longestNodeName + 15)
            . str_pad('Resources package', $longestSiteResource + 15)
            . 'Status ');
        $this->outputLine(
            str_repeat('-', $longestSiteName + $longestNodeName + $longestSiteResource + 7 + 15 + 15 + 15 + 2)
        );
        foreach ($availableSites as $site) {
            $this->outputLine(' ' . str_pad($site['name'], $longestSiteName + 15)
                . str_pad($site['nodeName'], $longestNodeName + 15)
                . str_pad($site['siteResourcesPackageKey'], $longestSiteResource + 15)
                . $site['status']);
        }
        $this->outputLine();
    }

    /**
     * Activate a site (with globbing)
     *
     * This command activates the specified site.
     *
     * @param string $siteNode The node name of the sites to activate (globbing is supported)
     * @return void
     */
    public function activateCommand($siteNode)
    {
        $sites = $this->findSitesByNodeNamePattern($siteNode);
        if (empty($sites)) {
            $this->outputLine('<error>No Site found for pattern "%s".</error>', [$siteNode]);
            $this->quit(1);
        }
        foreach ($sites as $site) {
            $site->setState(Site::STATE_ONLINE);
            $this->siteRepository->update($site);
            $this->outputLine('Site "%s" was activated.', [$site->getNodeName()]);
        }
    }

    /**
     * Deactivate a site (with globbing)
     *
     * This command deactivates the specified site.
     *
     * @param string $siteNode The node name of the sites to deactivate (globbing is supported)
     * @return void
     */
    public function deactivateCommand($siteNode)
    {
        $sites = $this->findSitesByNodeNamePattern($siteNode);
        if (empty($sites)) {
            $this->outputLine('<error>No Site found for pattern "%s".</error>', [$siteNode]);
            $this->quit(1);
        }
        foreach ($sites as $site) {
            $site->setState(Site::STATE_OFFLINE);
            $this->siteRepository->update($site);
            $this->outputLine('Site "%s" was deactivated.', [$site->getNodeName()]);
        }
    }

    /**
     * Find all sites the match the given site-node-name-pattern with support for globbing
     *
     * @param string $siteNodePattern nodeName patterns for sites to find
     * @return array<Site>
     */
    protected function findSitesByNodeNamePattern($siteNodePattern)
    {
        return array_filter(
            $this->siteRepository->findAll()->toArray(),
            function ($site) use ($siteNodePattern) {
                return fnmatch($siteNodePattern, $site->getNodeName());
            }
        );
    }
}
