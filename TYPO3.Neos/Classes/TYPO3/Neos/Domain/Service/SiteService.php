<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * A service for manipulating sites
 *
 * @Flow\Scope("singleton")
 */
class SiteService
{
    /**
     * This is the node path of the root for all sites in neos.
     */
    const SITES_ROOT_PATH = '/sites';

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Remove given site all nodes for that site and all domains associated.
     *
     * @param Site $site
     * @return void
     */
    public function pruneSite(Site $site)
    {
        $siteNodePath = NodePaths::addNodePathSegment(static::SITES_ROOT_PATH, $site->getNodeName());
        $this->nodeDataRepository->removeAllInPath($siteNodePath);
        $siteNodes = $this->nodeDataRepository->findByPath($siteNodePath);
        foreach ($siteNodes as $siteNode) {
            $this->nodeDataRepository->remove($siteNode);
        }

        $domainsForSite = $this->domainRepository->findBySite($site);
        foreach ($domainsForSite as $domain) {
            $this->domainRepository->remove($domain);
        }
        $this->persistenceManager->persistAll();

        $this->siteRepository->remove($site);

        $this->emitSitePruned($site);
    }

    /**
     * Remove all sites and their respective nodes and domains
     *
     * @return void
     */
    public function pruneAll()
    {
        foreach ($this->siteRepository->findAll() as $site) {
            $this->pruneSite($site);
        }
    }

    /**
     * Signal that is triggered whenever a site has been pruned
     *
     * @Flow\Signal
     * @param Site $site The site that was pruned
     * @return void
     */
    protected function emitSitePruned(Site $site)
    {
    }
}
