<?php
namespace TYPO3\Neos\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * A service for manipulating sites
 *
 * @Flow\Scope("singleton")
 */
class SiteService {

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
	 * Remove given site all nodes for that site and all domains associated.
	 *
	 * @param Site $site
	 * @return void
	 */
	public function pruneSite(Site $site) {
		$siteNodePath = '/sites/' . $site->getNodeName();
		$this->nodeDataRepository->removeAllInPath($siteNodePath);
		$siteNodes = $this->nodeDataRepository->findByPath($siteNodePath);
		foreach ($siteNodes as $siteNode) {
			$this->nodeDataRepository->remove($siteNode);
		}

		$domainsForSite = $this->domainRepository->findBySite($site);
		foreach ($domainsForSite as $domain) {
			$this->domainRepository->remove($domain);
		}
		$this->siteRepository->remove($site);
	}

	/**
	 * Remove all nodes, workspaces, domains and sites.
	 *
	 * @return void
	 */
	public function pruneAll() {
		$this->nodeDataRepository->removeAll();
		$this->workspaceRepository->removeAll();
		$this->domainRepository->removeAll();
		$this->siteRepository->removeAll();
	}
}