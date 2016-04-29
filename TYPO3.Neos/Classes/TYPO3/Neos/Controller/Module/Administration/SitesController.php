<?php
namespace TYPO3\Neos\Controller\Module\Administration;

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
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\Neos\Domain\Service\SiteService;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * The Neos Sites Management module controller
 */
class SitesController extends AbstractModuleController
{
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
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var SiteImportService
     */
    protected $siteImportService;

    /**
     * @Flow\Inject
     * @var SiteService
     */
    protected $siteService;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Session\SessionInterface
     */
    protected $session;

    /**
     * @return void
     */
    public function indexAction()
    {
        $sitePackagesAndSites = array();
        foreach ($this->packageManager->getFilteredPackages('available', null, 'typo3-flow-site') as $sitePackageKey => $sitePackage) {
            /** \TYPO3\Flow\Package\PackageInterface $sitePackage */
            $sitePackagesAndSites[strtolower(str_replace('.', '_', $sitePackageKey))] = array('package' => $sitePackage, 'packageKey' => $sitePackage->getPackageKey(), 'packageIsActive' => $this->packageManager->isPackageActive($sitePackage->getPackageKey()));
        }
        $sites = $this->siteRepository->findAll();
        foreach ($sites as $site) {
            $siteResourcePackageKey = strtolower(str_replace('.', '_', $site->getSiteResourcesPackageKey()));
            if (!isset($sitePackagesAndSites[$siteResourcePackageKey])) {
                $sitePackagesAndSites[$siteResourcePackageKey] = array('packageKey' => $site->getSiteResourcesPackageKey());
            }
            if (!isset($sitePackagesAndSites[$siteResourcePackageKey]['sites'])) {
                $sitePackagesAndSites[$siteResourcePackageKey]['sites'] = array();
            }
            $sitePackagesAndSites[$siteResourcePackageKey]['sites'][] = $site;
        }
        $this->view->assignMultiple(array(
            'sitePackagesAndSites' => $sitePackagesAndSites,
            'multipleSites' => count($sites) > 1
        ));
    }

    /**
     * A edit view for a site and its settings.
     *
     * @param Site $site Site to view
     * @Flow\IgnoreValidation("$site")
     * @return void
     */
    public function editAction(Site $site)
    {
        try {
            $sitePackage = $this->packageManager->getPackage($site->getSiteResourcesPackageKey());
        } catch (\Exception $e) {
            $this->addFlashMessage('The site package with key "%s" was not found.', 'Site package not found', Message::SEVERITY_ERROR, array(htmlspecialchars($site->getSiteResourcesPackageKey())));
        }

        $this->view->assignMultiple(array(
            'site' => $site,
            'sitePackageMetaData' => isset($sitePackage) ? $sitePackage->getPackageMetaData() : array(),
            'domains' => $this->domainRepository->findBySite($site),
            'assetCollections' => $this->assetCollectionRepository->findAll()
        ));
    }

    /**
     * Update a site
     *
     * @param Site $site A site to update
     * @param string $newSiteNodeName A new site node name
     * @return void
     * @Flow\Validate(argumentName="$site", type="UniqueEntity")
     * @Flow\Validate(argumentName="$newSiteNodeName", type="NotEmpty")
     * @Flow\Validate(argumentName="$newSiteNodeName", type="StringLength", options={ "minimum"=1, "maximum"=250 })
     * @Flow\Validate(argumentName="$newSiteNodeName", type="TYPO3.Neos:NodeName")
     */
    public function updateSiteAction(Site $site, $newSiteNodeName)
    {
        if ($site->getNodeName() !== $newSiteNodeName) {
            $oldSiteNodePath = NodePaths::addNodePathSegment(SiteService::SITES_ROOT_PATH, $site->getNodeName());
            $newSiteNodePath = NodePaths::addNodePathSegment(SiteService::SITES_ROOT_PATH, $newSiteNodeName);
            /** @var $workspace Workspace */
            foreach ($this->workspaceRepository->findAll() as $workspace) {
                $siteNode = $this->nodeDataRepository->findOneByPath($oldSiteNodePath, $workspace);
                if ($siteNode !== null) {
                    $siteNode->setPath($newSiteNodePath);
                }
            }
            $site->setNodeName($newSiteNodeName);
            $this->nodeDataRepository->persistEntities();
        }
        $this->siteRepository->update($site);
        $this->addFlashMessage('The site "%s" has been updated.', 'Update', null, array(htmlspecialchars($site->getName())), 1412371798);
        $this->unsetLastVisitedNodeAndRedirect('index');
    }

    /**
     * Create a new site form.
     *
     * @param Site $site Site to create
     * @Flow\IgnoreValidation("$site")
     * @return void
     */
    public function newSiteAction(Site $site = null)
    {
        $sitePackages = $this->packageManager->getFilteredPackages('available', null, 'typo3-flow-site');
        $this->view->assignMultiple(array(
            'sitePackages' => $sitePackages,
            'site' => $site,
            'generatorServiceIsAvailable' => $this->packageManager->isPackageActive('TYPO3.Neos.Kickstarter')
        ));
    }

    /**
     * Create a new site.
     *
     * @param string $site Site to import
     * @param string $packageKey Package Name to create
     * @param string $siteName Site Name to create
     * @Flow\Validate(argumentName="$packageKey", type="\TYPO3\Neos\Validation\Validator\PackageKeyValidator")
     * @return void
     */
    public function createSiteAction($site, $packageKey = '', $siteName = '')
    {
        if ($packageKey !== '' && $this->packageManager->isPackageActive('TYPO3.Neos.Kickstarter')) {
            if ($this->packageManager->isPackageAvailable($packageKey)) {
                $this->addFlashMessage('The package key "%s" already exists.', 'Invalid package key', Message::SEVERITY_ERROR, array(htmlspecialchars($packageKey)), 1412372021);
                $this->redirect('index');
            }

            $generatorService = $this->objectManager->get('TYPO3\Neos\Kickstarter\Service\GeneratorService');
            $generatorService->generateSitePackage($packageKey, $siteName);
        } else {
            $packageKey = $site;
        }

        $deactivatedSitePackages = $this->deactivateAllOtherSitePackages($packageKey);
        if (count($deactivatedSitePackages) > 0) {
            $this->flashMessageContainer->addMessage(new Message(sprintf('The existing Site Packages "%s" were deactivated, in order to prevent interactions with the newly created package "%s".', htmlspecialchars(implode(', ', $deactivatedSitePackages)), htmlspecialchars($packageKey))));
        }

        $this->packageManager->activatePackage($packageKey);

        if ($packageKey !== '') {
            try {
                $this->siteImportService->importFromPackage($packageKey);
                $this->addFlashMessage('The site has been created.', '', null, array(), 1412372266);
            } catch (\Exception $exception) {
                $this->systemLogger->logException($exception);
                $this->addFlashMessage('Error: During the import of the "Sites.xml" from the package "%s" an exception occurred: %s', 'Import error', Message::SEVERITY_ERROR, array(htmlspecialchars($packageKey), htmlspecialchars($exception->getMessage())), 1412372375);
            }
        } else {
            $this->addFlashMessage('No site selected for import and no package name provided.', 'No site selected', Message::SEVERITY_ERROR, array(), 1412372554);
            $this->redirect('newSite');
        }

        $this->unsetLastVisitedNodeAndRedirect('index');
    }

    /**
     * Delete a site.
     *
     * @param Site $site Site to create
     * @Flow\IgnoreValidation("$site")
     * @return void
     */
    public function deleteSiteAction(Site $site)
    {
        $this->siteService->pruneSite($site);
        $this->addFlashMessage('The site "%s" has been deleted.', 'Site deleted', Message::SEVERITY_OK, array(htmlspecialchars($site->getName())), 1412372689);
        $this->unsetLastVisitedNodeAndRedirect('index');
    }

    /**
     * Activates a site
     *
     * @param Site $site Site to update
     * @return void
     */
    public function activateSiteAction(Site $site)
    {
        $site->setState($site::STATE_ONLINE);
        $this->siteRepository->update($site);
        $this->addFlashMessage('The site "%s" has been activated.', 'Site activated', Message::SEVERITY_OK, array(htmlspecialchars($site->getName())), 1412372881);
        $this->unsetLastVisitedNodeAndRedirect('index');
    }

    /**
     * Deactivates a site
     *
     * @param Site $site Site to deactivate
     * @return void
     */
    public function deactivateSiteAction(Site $site)
    {
        $site->setState($site::STATE_OFFLINE);
        $this->siteRepository->update($site);
        $this->addFlashMessage('The site "%s" has been deactivated.', 'Site deactivated', Message::SEVERITY_OK, array(htmlspecialchars($site->getName())), 1412372975);
        $this->unsetLastVisitedNodeAndRedirect('index');
    }

    /**
     * Edit a domain
     *
     * @param Domain $domain Domain to edit
     * @Flow\IgnoreValidation("$domain")
     * @return void
     */
    public function editDomainAction(Domain $domain)
    {
        $this->view->assignMultiple(['domain' => $domain, 'schemes' => [null => '', 'http' => 'HTTP', 'https' => 'HTTPS']]);
    }

    /**
     * Update a domain
     *
     * @param Domain $domain Domain to update
     * @Flow\Validate(argumentName="$domain", type="UniqueEntity")
     * @return void
     */
    public function updateDomainAction(Domain $domain)
    {
        $this->domainRepository->update($domain);
        $this->addFlashMessage('The domain "%s" has been updated.', 'Domain updated', Message::SEVERITY_OK, array(htmlspecialchars($domain)), 1412373069);
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, array('site' => $domain->getSite()));
    }

    /**
     * The create a new domain action.
     *
     * @param Domain $domain
     * @param Site $site
     * @Flow\IgnoreValidation("$domain")
     * @return void
     */
    public function newDomainAction(Domain $domain = null, Site $site = null)
    {
        $this->view->assignMultiple(array(
            'domain' => $domain,
            'site' => $site,
            'schemes' => [null => '', 'http' => 'HTTP', 'https' => 'HTTPS']
        ));
    }

    /**
     * Create a domain
     *
     * @param Domain $domain Domain to create
     * @Flow\Validate(argumentName="$domain", type="UniqueEntity")
     * @return void
     */
    public function createDomainAction(Domain $domain)
    {
        $this->domainRepository->add($domain);
        $this->addFlashMessage('The domain "%s" has been created.', 'Domain created', Message::SEVERITY_OK, array(htmlspecialchars($domain)), 1412373192);
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, array('site' => $domain->getSite()));
    }

    /**
     * Deletes a domain attached to a site
     *
     * @param Domain $domain A domain to delete
     * @Flow\IgnoreValidation("$domain")
     * @return void
     */
    public function deleteDomainAction(Domain $domain)
    {
        $this->domainRepository->remove($domain);
        $this->addFlashMessage('The domain "%s" has been deleted.', 'Domain deleted', Message::SEVERITY_OK, array(htmlspecialchars($domain)), 1412373310);
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, array('site' => $domain->getSite()));
    }

    /**
     * Activates a domain
     *
     * @param Domain $domain Domain to activate
     * @Flow\IgnoreValidation("$domain")
     * @return void
     */
    public function activateDomainAction(Domain $domain)
    {
        $domain->setActive(true);
        $this->domainRepository->update($domain);
        $this->addFlashMessage('The domain "%s" has been activated.', 'Domain activated', Message::SEVERITY_OK, array(htmlspecialchars($domain)), 1412373539);
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, array('site' => $domain->getSite()));
    }

    /**
     * Deactivates a domain
     *
     * @param Domain $domain Domain to deactivate
     * @Flow\IgnoreValidation("$domain")
     * @return void
     */
    public function deactivateDomainAction(Domain $domain)
    {
        $domain->setActive(false);
        $this->domainRepository->update($domain);
        $this->addFlashMessage('The domain "%s" has been deactivated.', 'Domain deactivated', Message::SEVERITY_OK, array(htmlspecialchars($domain)), 1412373425);
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, array('site' => $domain->getSite()));
    }

    /**
     * @param string $actionName Name of the action to forward to
     * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
     * @param array $arguments Array of arguments for the target action
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @param string $format The format to use for the redirect URI
     * @return void
     */
    protected function unsetLastVisitedNodeAndRedirect($actionName, $controllerName = null, $packageKey = null, array $arguments = null, $delay = 0, $statusCode = 303, $format = null)
    {
        $this->session->putData('lastVisitedNode', null);
        parent::redirect($actionName, $controllerName, $packageKey, $arguments, $delay, $statusCode, $format);
    }

    /**
     * If site packages already exist and are active, we will deactivate them in order to prevent
     * interactions with the newly created or imported package (like Content Dimensions being used).
     *
     * @param string $activePackageKey Package key of one package which should stay active
     * @return array deactivated site packages
     */
    protected function deactivateAllOtherSitePackages($activePackageKey)
    {
        $sitePackagesToDeactivate = $this->packageManager->getFilteredPackages('active', null, 'typo3-flow-site');
        $deactivatedSitePackages = array();

        foreach (array_keys($sitePackagesToDeactivate) as $packageKey) {
            if ($packageKey !== $activePackageKey) {
                $this->packageManager->deactivatePackage($packageKey);
                $deactivatedSitePackages[] = $packageKey;
            }
        }

        return $deactivatedSitePackages;
    }
}
