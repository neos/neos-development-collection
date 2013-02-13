<?php
namespace TYPO3\Neos\Controller\Module\Administration;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Neos".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow,
	TYPO3\Flow\Utility\Files as Files;

/**
 * The TYPO3 Sites Management module controller
 *
 * @Flow\Scope("singleton")
 */
class SitesController extends \TYPO3\Neos\Controller\Module\StandardController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\SiteImportService
	 */
	protected $siteImportService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('sites', $this->siteRepository->findAll());
	}

	/**
	 * A edit view for a site and its settings.
	 *
	 * @param \TYPO3\Neos\Domain\Model\Site $site Site to view
	 * @Flow\IgnoreValidation("$site")
	 * @return void
	 */
	public function editAction(\TYPO3\Neos\Domain\Model\Site $site) {
		$sitePackage = $this->packageManager->getPackage($site->getSiteResourcesPackageKey());
		$this->view->assignMultiple(array(
			'site' => $site,
			'sitePackageMetaData' => $sitePackage->getPackageMetaData(),
			'domains' => $this->domainRepository->findBySite($site)
		));
	}

	/**
	 * Update a site
	 *
	 * @param \TYPO3\Neos\Domain\Model\Site $site A site to update
	 * @param string $originalNodeName The site's original node name
	 * @Flow\Validate(argumentName="site", type="UniqueEntity")
	 * @Flow\Validate(argumentName="originalNodeName", type="NotEmpty")
	 * @return void
	 */
	public function updateSiteAction(\TYPO3\Neos\Domain\Model\Site $site, $originalNodeName) {
		if ($site->getNodeName() !== $originalNodeName) {
			$siteNode = $this->propertyMapper->convert('/sites/' . $originalNodeName, 'TYPO3\TYPO3CR\Domain\Model\NodeInterface');
			$siteNode->setName($site->getName());
		}
		$this->siteRepository->update($site);
		$this->addFlashMessage(sprintf('The site "%s" has been updated.', $site->getName()));
		$this->redirect('index');
	}

	/**
	 * Create a new site form.
	 *
	 * @param \TYPO3\Neos\Domain\Model\Site $site Site to create
	 * @Flow\IgnoreValidation("$site")
	 * @return void
	 */
	public function newSiteAction(\TYPO3\Neos\Domain\Model\Site $site = NULL) {
		$packageGroups = array();
		foreach ($this->packageManager->getAvailablePackages() as $package) {
			$packagePath = substr($package->getPackagepath(), strlen(FLOW_PATH_PACKAGES));
			$packageGroup = substr($packagePath, 0, strpos($packagePath, '/'));
			if ($packageGroup === 'Sites') {
				$packageGroups[] = $package;
			}
		}
		$this->view->assignMultiple(array(
			'sitePackages' => $packageGroups,
			'site' => $site,
			'generatorServiceIsAvailable' => $this->packageManager->isPackageActive('TYPO3.SiteKickstarter')
		));
	}

	/**
	 * Create a new site.
	 *
	 * @param string $site Site to import
	 * @param string $packageKey Package Name to create
	 * @param string $siteName Site Name to create
	 * @Flow\Validate(argumentName="packageKey", type="\TYPO3\Neos\Validation\Validator\PackageKeyValidator")
	 * @return void
	 */
	public function createSiteAction($site, $packageKey, $siteName) {
		if ($packageKey !== '' && $this->packageManager->isPackageActive('TYPO3.SiteKickstarter')) {
			if ($this->packageManager->isPackageAvailable($packageKey)) {
				$this->addFlashMessage(
					sprintf('The package key "%s" already exists.', $packageKey),
					'',
					\TYPO3\Flow\Error\Message::SEVERITY_ERROR
				);
				$this->redirect('index');
			}

			$this->packageManager->createPackage($packageKey, NULL, Files::getUnixStylePath(Files::concatenatePaths(array(FLOW_PATH_PACKAGES, 'Sites'))));
			$generatorService = $this->objectManager->get('TYPO3\SiteKickstarter\Service\GeneratorService');
			$generatorService->generateSitesXml($packageKey, $siteName);
			$generatorService->generateSitesTypoScript($packageKey, $siteName);
			$generatorService->generateSitesTemplate($packageKey, $siteName);
			$this->packageManager->activatePackage($packageKey);
		} else {
			$packageKey = $site;
		}

		if ($packageKey !== '') {
			try {
				$contentContext = new \TYPO3\Neos\Domain\Service\ContentContext('live');
				$this->nodeRepository->setContext($contentContext);
				$this->siteImportService->importFromPackage($packageKey);
				$this->addFlashMessage('The site has been created.');
			} catch (\Exception $exception) {
				$this->systemLogger->logException($exception);
				$this->addFlashMessage(
					sprintf('Error: During the import of the "Sites.xml" from the package "%s" an exception occurred: %s', $packageKey, $exception->getMessage()),
					'',
					\TYPO3\Flow\Error\Message::SEVERITY_ERROR
				);
			}
		} else {
			$this->addFlashMessage(
				'No site selected for import and no package name provided.',
				'',
				\TYPO3\Flow\Error\Message::SEVERITY_ERROR
			);
			$this->redirect('newSite');
		}

		$this->redirect('index');
	}

	/**
	 * Delete a site.
	 *
	 * @param \TYPO3\Neos\Domain\Model\Site $site Site to create
	 * @Flow\IgnoreValidation("$site")
	 * @return void
	 */
	public function deleteSiteAction(\TYPO3\Neos\Domain\Model\Site $site) {
		$domains = $this->domainRepository->findBySite($site);
		if (count($domains) > 0) {
			foreach ($domains as $domain) {
				$this->domainRepository->remove($domain);
			}
		}
		$this->siteRepository->remove($site);
		$siteNode = $this->propertyMapper->convert('/sites/' . $site->getNodeName(), 'TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$siteNode->remove();
		$this->addFlashMessage(sprintf('The site "%s" has been deleted.', $site->getName()));
		$this->redirect('index');
	}

	/**
	 * Activates a site
	 *
	 * @param \TYPO3\Neos\Domain\Model\Site $site Site to update
	 * @return void
	 */
	public function activateSiteAction(\TYPO3\Neos\Domain\Model\Site $site) {
		$site->setState($site::STATE_ONLINE);
		$this->siteRepository->update($site);
		$this->addFlashMessage(sprintf('The site "%s" has been activated.', $site->getName()));
		$this->redirect('index');
	}

	/**
	 * Deactivates a site
	 *
	 * @param \TYPO3\Neos\Domain\Model\Site $site Site to deactivate
	 * @return void
	 */
	public function deactivateSiteAction(\TYPO3\Neos\Domain\Model\Site $site) {
		$site->setState($site::STATE_OFFLINE);
		$this->siteRepository->update($site);
		$this->addFlashMessage(sprintf('The site "%s" has been deactivated.', $site->getName()));
		$this->redirect('index');
	}

	/**
	 * Edit a domain
	 *
	 * @param \TYPO3\Neos\Domain\Model\Domain $domain Domain to edit
	 * @Flow\IgnoreValidation("$domain")
	 * @return void
	 */
	public function editDomainAction(\TYPO3\Neos\Domain\Model\Domain $domain) {
		$this->view->assign('domain', $domain);
	}

	/**
	 * Update a domain
	 *
	 * @param \TYPO3\Neos\Domain\Model\Domain $domain Domain to update
	 * @Flow\Validate(argumentName="domain", type="UniqueEntity")
	 * @return void
	 */
	public function updateDomainAction(\TYPO3\Neos\Domain\Model\Domain $domain) {
		$this->domainRepository->update($domain);
		$this->addFlashMessage(sprintf('The domain "%s" has been updated.', $domain->getHostPattern()));
		$this->redirect('edit', NULL, NULL, array('site' => $domain->getSite()));
	}

	/**
	 * The create a new domain action.
	 *
	 * @param \TYPO3\Neos\Domain\Model\Domain $domain
	 * @param \TYPO3\Neos\Domain\Model\Site $site
	 * @Flow\IgnoreValidation("$domain")
	 * @return void
	 */
	public function newDomainAction(\TYPO3\Neos\Domain\Model\Domain $domain = NULL, \TYPO3\Neos\Domain\Model\Site $site = NULL) {
		$this->view->assignMultiple(array(
			'domain' => $domain,
			'site' => $site
		));
	}

	/**
	 * Create a domain
	 *
	 * @param \TYPO3\Neos\Domain\Model\Domain $domain Domain to create
	 * @Flow\Validate(argumentName="domain", type="UniqueEntity")
	 * @return void
	 */
	public function createDomainAction(\TYPO3\Neos\Domain\Model\Domain $domain) {
		$this->domainRepository->add($domain);
		$this->addFlashMessage(sprintf('The domain "%s" has been created.', $domain->getHostPattern()));
		$this->redirect('edit', NULL, NULL, array('site' => $domain->getSite()));
	}

	/**
	 * Deletes a domain attached to a site
	 *
	 * @param \TYPO3\Neos\Domain\Model\Domain $domain A domain to delete
	 * @Flow\IgnoreValidation("$domain")
	 * @return void
	 */
	public function deleteDomainAction(\TYPO3\Neos\Domain\Model\Domain $domain) {
		$this->domainRepository->remove($domain);
		$this->addFlashMessage(sprintf('The domain "%s" has been deleted.', $domain->getHostPattern()));
		$this->redirect('edit', NULL, NULL, array('site' => $domain->getSite()));
	}

	/**
	 * Activates a domain
	 *
	 * @param \TYPO3\Neos\Domain\Model\Domain $domain Domain to activate
	 * @return void
	 */
	public function activateDomainAction(\TYPO3\Neos\Domain\Model\Domain $domain) {
		$domain->setActive(TRUE);
		$this->domainRepository->update($domain);
		$this->addFlashMessage(sprintf('The domain "%s" has been activated.', $domain->getHostPattern()));
		$this->redirect('edit', NULL, NULL, array('site' => $domain->getSite()));
	}

	/**
	 * Deactivates a domain
	 *
	 * @param \TYPO3\Neos\Domain\Model\Domain $domain Domain to deactivate
	 * @return void
	 */
	public function deactivateDomainAction(\TYPO3\Neos\Domain\Model\Domain $domain) {
		$domain->setActive(FALSE);
		$this->domainRepository->update($domain);
		$this->addFlashMessage(sprintf('The domain "%s" has been deactivated.', $domain->getHostPattern()));
		$this->redirect('edit', NULL, NULL, array('site' => $domain->getSite()));
	}

}
?>