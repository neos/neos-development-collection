<?php
namespace Neos\Neos\Controller\Module\Administration;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Message;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Session\SessionInterface;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\Domain\Service\SiteService;
use Neos\SiteKickstarter\Service\GeneratorService;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\Service\NodeService;

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
     * @var ContextFactoryInterface
     */
    protected $nodeContextFactory;

    /**
     * @Flow\Inject
     * @var NodeService
     */
    protected $nodeService;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

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
     * @var SessionInterface
     */
    protected $session;

    /**
     * @return void
     */
    public function indexAction()
    {
        $sitePackagesAndSites = array();
        foreach ($this->packageManager->getFilteredPackages('available', null, 'neos-site') as $sitePackageKey => $sitePackage) {
            /** @var PackageInterface $sitePackage */
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
            'sitePackage' => isset($sitePackage) ? $sitePackage : array(),
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
     * @Flow\Validate(argumentName="$newSiteNodeName", type="Neos.Neos:NodeName")
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
        $sitePackages = $this->packageManager->getFilteredPackages('active', null, 'neos-site');
        $documentNodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.Neos:Document', false);
        $this->view->assignMultiple(array(
            'sitePackages' => $sitePackages,
            'documentNodeTypes' => $documentNodeTypes,
            'site' => $site,
            'generatorServiceIsAvailable' => $this->packageManager->isPackageActive('Neos.SiteKickstarter')
        ));
    }

    /**
     * Create a new site-package and directly import it.
     *
     * @param string $packageKey Package Name to create
     * @param string $siteName Site Name to create
     * @Flow\Validate(argumentName="$packageKey", type="\Neos\Neos\Validation\Validator\PackageKeyValidator")
     * @return void
     */
    public function createSitePackageAction($packageKey, $siteName)
    {
        if ($this->packageManager->isPackageActive('Neos.SiteKickstarter') === false) {
            $this->addFlashMessage('The package "%s" is required to create new site packages.', 'Missing Package', Message::SEVERITY_ERROR, array('Neos.SiteKickstarter'), 1475736232);
            $this->redirect('index');
        }

        if ($this->packageManager->isPackageAvailable($packageKey)) {
            $this->addFlashMessage('The package key "%s" already exists.', 'Invalid package key', Message::SEVERITY_ERROR, array(htmlspecialchars($packageKey)), 1412372021);
            $this->redirect('index');
        }

        $generatorService = $this->objectManager->get(GeneratorService::class);
        $generatorService->generateSitePackage($packageKey, $siteName);

        $this->flashMessageContainer->addMessage(new Message(sprintf('Site Packages "%s" was created.', htmlspecialchars($packageKey))));

        $deactivatedSitePackages = $this->deactivateAllOtherSitePackages($packageKey);
        if (count($deactivatedSitePackages) > 0) {
            $this->flashMessageContainer->addMessage(new Message(sprintf('The existing Site Packages "%s" were deactivated, in order to prevent interactions with the newly created package "%s".', htmlspecialchars(implode(', ', $deactivatedSitePackages)), htmlspecialchars($packageKey))));
        }

        $this->packageManager->activatePackage($packageKey);

        $this->forward('importSite', null, null, ['packageKey' => $packageKey]);
    }

    /**
     * Import a site from site package.
     *
     * @param string $packageKey Package from where the import will come
     * @Flow\Validate(argumentName="$packageKey", type="\Neos\Neos\Validation\Validator\PackageKeyValidator")
     * @return void
     */
    public function importSiteAction($packageKey)
    {
        try {
            $this->siteImportService->importFromPackage($packageKey);
            $this->addFlashMessage('The site has been imported.', '', null, array(), 1412372266);
        } catch (\Exception $exception) {
            $this->systemLogger->logException($exception);
            $this->addFlashMessage('Error: During the import of the "Sites.xml" from the package "%s" an exception occurred: %s', 'Import error', Message::SEVERITY_ERROR, array(htmlspecialchars($packageKey), htmlspecialchars($exception->getMessage())), 1412372375);
        }
        $this->unsetLastVisitedNodeAndRedirect('index');
    }

    /**
     * Create a new empty site.
     *
     * @param string $packageKey Package Name to create
     * @param string $siteName Site Name to create
     * @param string $nodeType NodeType name for the root node to create
     * @Flow\Validate(argumentName="$packageKey", type="\Neos\Neos\Validation\Validator\PackageKeyValidator")
     * @return void
     */
    public function createSiteNodeAction($packageKey, $siteName, $nodeType)
    {
        $nodeName = $this->nodeService->generateUniqueNodeName(SiteService::SITES_ROOT_PATH, $siteName);

        if ($this->siteRepository->findOneByNodeName($nodeName)) {
            $this->addFlashMessage('Error:A site with siteNodeName "%s" already exists', Message::SEVERITY_ERROR, [$nodeName], 1412372375);
            $this->redirect('createSiteNode');
        }

        $siteNodeType = $this->nodeTypeManager->getNodeType($nodeType);

        if ($siteNodeType === null || $siteNodeType->getName() === 'Neos.Neos:FallbackNode') {
            $this->addFlashMessage('Error: The given node type "%s" was not found', 'Import error', Message::SEVERITY_ERROR, [$nodeType], 1412372375);
            $this->redirect('createSiteNode');
        }

        if ($siteNodeType->isOfType('Neos.Neos:Document') === false) {
            $this->addFlashMessage('Error: The given node type "%s" is not based on the superType "%s"', Message::SEVERITY_ERROR, [$nodeType, 'Neos.Neos:Document'], 1412372375);
            $this->redirect('createSiteNode');
        }

        $rootNode = $this->nodeContextFactory->create()->getRootNode();

        // We fetch the workspace to be sure it's known to the persistence manager and persist all
        // so the workspace and site node are persisted before we import any nodes to it.
        $rootNode->getContext()->getWorkspace();
        $this->persistenceManager->persistAll();
        $sitesNode = $rootNode->getNode(SiteService::SITES_ROOT_PATH);
        if ($sitesNode === null) {
            $sitesNode = $rootNode->createNode(NodePaths::getNodeNameFromPath(SiteService::SITES_ROOT_PATH));
        }
        $siteNode = $sitesNode->createNode($nodeName, $siteNodeType);
        $siteNode->setProperty('title', $siteName);
        $site = new Site($nodeName);
        $site->setSiteResourcesPackageKey($packageKey);
        $site->setState(Site::STATE_ONLINE);
        $site->setName($siteName);
        $this->siteRepository->add($site);

        $this->addFlashMessage('Successfully created site "%s" with siteNode "%s", type "%s" and packageKey "%s"', '', null, [$siteName, $nodeName, $nodeType, $packageKey], 1412372266);
        $this->unsetLastVisitedNodeAndRedirect('index');
    }

    /**
     * Delete a site.
     *
     * @param Site $site Site to delete
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
     * @param Site $site Site to activate
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
        $site = $domain->getSite();
        if ($site->getPrimaryDomain() === $domain) {
            $site->setPrimaryDomain(null);
            $this->siteRepository->update($site);
        }
        $this->domainRepository->remove($domain);
        $this->addFlashMessage('The domain "%s" has been deleted.', 'Domain deleted', Message::SEVERITY_OK, array(htmlspecialchars($domain)), 1412373310);
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, array('site' => $site));
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
        $sitePackagesToDeactivate = $this->packageManager->getFilteredPackages('active', null, 'neos-site');
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
