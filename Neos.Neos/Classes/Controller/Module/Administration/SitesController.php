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

namespace Neos\Neos\Controller\Module\Administration;

use Neos\ContentRepository\Feature\Common\Exception\NodeNameIsAlreadyOccupied;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Message;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Session\SessionInterface;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Controller\Module\ModuleTranslationTrait;
use Neos\Neos\Domain\Exception\SiteNodeNameIsAlreadyInUseByAnotherSite;
use Neos\Neos\Domain\Exception\SiteNodeTypeIsInvalid;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\Domain\Service\SiteService;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\Neos\Domain\Service\UserService;
use Neos\SiteKickstarter\Generator\SitePackageGeneratorInterface;
use Neos\SiteKickstarter\Service\SiteGeneratorCollectingService;
use Neos\SiteKickstarter\Service\SitePackageGeneratorNameService;

/**
 * The Neos Sites Management module controller
 */
class SitesController extends AbstractModuleController
{
    use ModuleTranslationTrait;

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
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @Flow\Inject
     * @var PackageManager
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
     * @var SessionInterface
     */
    protected $session;

    #[Flow\Inject]
    protected UserService $domainUserService;

    #[Flow\Inject]
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    #[Flow\Inject]
    protected ContentGraphInterface $contentGraph;

    /**
     * @return void
     */
    public function indexAction()
    {
        $sitePackagesAndSites = [];
        foreach (
            $this->packageManager->getFilteredPackages(
                'available',
                'neos-site'
            ) as $sitePackageKey => $sitePackage
        ) {
            /** @var Package $sitePackage */
            $sitePackagesAndSites[strtolower(str_replace('.', '_', $sitePackageKey))] = [
                'package' => $sitePackage,
                'packageKey' => $sitePackage->getPackageKey()
            ];
        }
        $sites = $this->siteRepository->findAll();
        foreach ($sites as $site) {
            $siteResourcePackageKey = strtolower(str_replace('.', '_', $site->getSiteResourcesPackageKey()));
            if (!isset($sitePackagesAndSites[$siteResourcePackageKey])) {
                $sitePackagesAndSites[$siteResourcePackageKey] = ['packageKey' => $site->getSiteResourcesPackageKey()];
            }
            if (!isset($sitePackagesAndSites[$siteResourcePackageKey]['sites'])) {
                $sitePackagesAndSites[$siteResourcePackageKey]['sites'] = [];
            }
            $sitePackagesAndSites[$siteResourcePackageKey]['sites'][] = $site;
        }
        $this->view->assignMultiple([
            'sitePackagesAndSites' => $sitePackagesAndSites,
            'multipleSites' => count($sites) > 1
        ]);
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
            $this->addFlashMessage(
                $this->getModuleLabel(
                    'sites.sitePackageNotFound.body',
                    [htmlspecialchars($site->getSiteResourcesPackageKey())]
                ),
                $this->getModuleLabel('sites.sitePackageNotFound.title'),
                Message::SEVERITY_ERROR
            );
        }

        $this->view->assignMultiple([
            'site' => $site,
            'sitePackage' => $sitePackage ?? [],
            'domains' => $this->domainRepository->findBySite($site),
            'assetCollections' => $this->assetCollectionRepository->findAll()
        ]);
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
            $this->redirect('index');
        }

        $liveWorkspace = $this->workspaceFinder->findOneByName(WorkspaceName::forLive());
        if (!$liveWorkspace instanceof Workspace) {
            throw new \InvalidArgumentException(
                'Cannot update a site without the live workspace being present.',
                1651958443
            );
        }

        try {
            $sitesNode = $this->contentGraph->findRootNodeAggregateByType(
                $liveWorkspace->getCurrentContentStreamIdentifier(),
                NodeTypeName::fromString('Neos.Neos:Sites')
            );
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException(
                'Cannot update a site without the sites note being present.',
                1651958452
            );
        }

        $currentUser = $this->domainUserService->getCurrentUser();
        if (is_null($currentUser)) {
            throw new \InvalidArgumentException(
                'Cannot update a site without a current user',
                1651958722
            );
        }

        foreach ($this->workspaceFinder->findAll() as $workspace) {
            // technically, due to the name being the "identifier", there might be more than one :/
            $siteNodeAggregates = $this->contentGraph->findChildNodeAggregatesByName(
                $workspace->getCurrentContentStreamIdentifier(),
                $sitesNode->getIdentifier(),
                NodeName::fromString($site->getNodeName())
            );

            foreach ($siteNodeAggregates as $siteNodeAggregate) {
                $this->nodeAggregateCommandHandler->handleChangeNodeAggregateName(new ChangeNodeAggregateName(
                    $workspace->getCurrentContentStreamIdentifier(),
                    $siteNodeAggregate->getIdentifier(),
                    NodeName::fromString($newSiteNodeName),
                    $this->persistenceManager->getIdentifierByObject($currentUser)
                ));
            }
        }

        $site->setNodeName($newSiteNodeName);
        $this->siteRepository->update($site);

        $this->addFlashMessage(
            $this->getModuleLabel('sites.update.body', [htmlspecialchars($site->getName())]),
            $this->getModuleLabel('sites.update.title'),
            Message::SEVERITY_OK,
            [],
            1412371798
        );
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
        $sitePackages = $this->packageManager->getFilteredPackages('available', 'neos-site');
        $documentNodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.Neos:Document', false);

        $generatorServiceIsAvailable = $this->packageManager->isPackageAvailable('Neos.SiteKickstarter');
        $generatorServices = [];

        if ($generatorServiceIsAvailable) {
            /** @var SiteGeneratorCollectingService $siteGeneratorCollectingService */
            $siteGeneratorCollectingService = $this->objectManager->get(SiteGeneratorCollectingService::class);
            /** @var SitePackageGeneratorNameService $sitePackageGeneratorNameService */
            $sitePackageGeneratorNameService = $this->objectManager->get(SitePackageGeneratorNameService::class);

            $generatorClasses = $siteGeneratorCollectingService->getAllGenerators();

            foreach ($generatorClasses as $generatorClass) {
                $name = $sitePackageGeneratorNameService->getNameOfSitePackageGenerator($generatorClass);
                $generatorServices[$generatorClass] = $name;
            }
        }

        $this->view->assignMultiple([
            'sitePackages' => $sitePackages,
            'documentNodeTypes' => $documentNodeTypes,
            'site' => $site,
            'generatorServiceIsAvailable' => $generatorServiceIsAvailable,
            'generatorServices' => $generatorServices
        ]);
    }

    /**
     * Create a new site-package and directly import it.
     *
     * @param string $packageKey Package Name to create
     * @param string $generatorClass Generator Class to generate the site package
     * @param string $siteName Site Name to create
     * @Flow\Validate(argumentName="$packageKey", type="\Neos\Neos\Validation\Validator\PackageKeyValidator")
     * @return void
     */
    public function createSitePackageAction(string $packageKey, string $generatorClass, string $siteName): void
    {
        if ($this->packageManager->isPackageAvailable('Neos.SiteKickstarter') === false) {
            $this->addFlashMessage(
                $this->getModuleLabel('sites.missingPackage.body', ['Neos.SiteKickstarter']),
                $this->getModuleLabel('sites.missingPackage.title'),
                Message::SEVERITY_ERROR,
                [],
                1475736232
            );
            $this->redirect('index');
        }

        if ($this->packageManager->isPackageAvailable($packageKey)) {
            $this->addFlashMessage(
                $this->getModuleLabel('sites.invalidPackageKey.body', [htmlspecialchars($packageKey)]),
                $this->getModuleLabel('sites.invalidPackageKey.title'),
                Message::SEVERITY_ERROR,
                [],
                1412372021
            );
            $this->redirect('index');
        }
        // this should never happen, but if somebody posts unexpected data to the form,
        // it should stop here and return some readable error message
        if ($this->objectManager->has($generatorClass) === false) {
            $this->addFlashMessage(
                'The generator class "%s" is not present.',
                'Missing generator class',
                Message::SEVERITY_ERROR,
                [$generatorClass]
            );
            $this->redirect('index');
        }

        /** @var SitePackageGeneratorInterface $generatorService */
        $generatorService = $this->objectManager->get($generatorClass);
        $generatorService->generateSitePackage($packageKey, $siteName);

        $this->controllerContext->getFlashMessageContainer()->addMessage(new Message(sprintf(
            $this->getModuleLabel('sites.sitePackagesWasCreated.body', [htmlspecialchars($packageKey)]),
            '',
            null
        )));
        $this->forward('importSite', null, null, ['packageKey' => $packageKey]);
    }

    /**
     * Import a site from site package.
     *
     * @param string $packageKey Package from where the import will come
     * @Flow\Validate(argumentName="$packageKey", type="\Neos\Neos\Validation\Validator\PackageKeyValidator")
     * @return void
     */
    /*public function importSiteAction($packageKey)
    {
        try {
            $this->siteImportService->importFromPackage($packageKey);
            $this->addFlashMessage(
                $this->getModuleLabel('sites.theSiteHasBeenImported.body'),
                '',
                Message::SEVERITY_OK,
                [],
                1412372266
            );
        } catch (\Exception $exception) {
            $logMessage = $this->throwableStorage->logThrowable($exception);
            $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
            $this->addFlashMessage(
                $this->getModuleLabel(
                    'sites.importError.body',
                    [htmlspecialchars($packageKey), htmlspecialchars($exception->getMessage())]
                ),
                $this->getModuleLabel('sites.importError.title'),
                Message::SEVERITY_ERROR,
                [],
                1412372375
            );
        }
        $this->unsetLastVisitedNodeAndRedirect('index');
    }*/

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
        try {
            $site = $this->siteService->createSite($packageKey, $siteName, $nodeType);
        } catch (NodeTypeNotFoundException $exception) {
            $this->addFlashMessage(
                $this->getModuleLabel('sites.siteCreationError.givenNodeTypeNotFound.body', [$nodeType]),
                $this->getModuleLabel('sites.siteCreationError.givenNodeTypeNotFound.title'),
                Message::SEVERITY_ERROR,
                [],
                1412372375
            );
            $this->redirect('createSiteNode');
            return;
        } catch (SiteNodeTypeIsInvalid $exception) {
            $this->addFlashMessage(
                $this->getModuleLabel(
                    'sites.siteCreationError.givenNodeTypeNotBasedOnSuperType.body',
                    [$nodeType, NodeTypeNameFactory::forSite()]
                ),
                $this->getModuleLabel('sites.siteCreationError.givenNodeTypeNotBasedOnSuperType.title'),
                Message::SEVERITY_ERROR,
                [],
                1412372375
            );
            $this->redirect('createSiteNode');
            return;
        } catch (SiteNodeNameIsAlreadyInUseByAnotherSite | NodeNameIsAlreadyOccupied $exception) {
            $this->addFlashMessage(
                $this->getModuleLabel('sites.SiteCreationError.siteWithSiteNodeNameAlreadyExists.body', [$siteName]),
                $this->getModuleLabel('sites.SiteCreationError.siteWithSiteNodeNameAlreadyExists.title'),
                Message::SEVERITY_ERROR,
                [],
                1412372375
            );
            $this->redirect('createSiteNode');
            return;
        }

        $this->addFlashMessage(
            $this->getModuleLabel(
                'sites.successfullyCreatedSite.body',
                [$site->getName(), $site->getNodeName(), $nodeType, $packageKey]
            ),
            '',
            Message::SEVERITY_OK,
            [],
            1412372266
        );
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
        $this->addFlashMessage(
            $this->getModuleLabel('sites.siteDeleted.body', [htmlspecialchars($site->getName())]),
            $this->getModuleLabel('sites.siteDeleted.title'),
            Message::SEVERITY_OK,
            [],
            1412372689
        );
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
        $this->addFlashMessage(
            $this->getModuleLabel('sites.siteActivated.body', [htmlspecialchars($site->getName())]),
            $this->getModuleLabel('sites.siteActivated.title'),
            Message::SEVERITY_OK,
            [],
            1412372881
        );
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
        $this->addFlashMessage(
            $this->getModuleLabel('sites.siteDeactivated.body', [htmlspecialchars($site->getName())]),
            $this->getModuleLabel('sites.siteDeactivated.title'),
            Message::SEVERITY_OK,
            [],
            1412372975
        );
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
        $this->view->assignMultiple([
            'domain' => $domain,
            'schemes' => [null => '', 'http' => 'HTTP', 'https' => 'HTTPS']
        ]);
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
        $this->addFlashMessage(
            $this->getModuleLabel('sites.domainUpdated.body', [htmlspecialchars($domain)]),
            $this->getModuleLabel('sites.domainUpdated.title'),
            Message::SEVERITY_OK,
            [],
            1412373069
        );
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, ['site' => $domain->getSite()]);
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
        $this->view->assignMultiple([
            'domain' => $domain,
            'site' => $site,
            'schemes' => [null => '', 'http' => 'HTTP', 'https' => 'HTTPS']
        ]);
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
        $this->addFlashMessage(
            $this->getModuleLabel('sites.domainCreated.body', [htmlspecialchars($domain)]),
            $this->getModuleLabel('sites.domainCreated.title'),
            Message::SEVERITY_OK,
            [],
            1412373192
        );
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, ['site' => $domain->getSite()]);
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
        $this->addFlashMessage(
            $this->getModuleLabel('sites.domainDeleted.body', [htmlspecialchars($domain)]),
            $this->getModuleLabel('sites.domainDeleted.title'),
            Message::SEVERITY_OK,
            [],
            1412373310
        );
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, ['site' => $site]);
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
        $this->addFlashMessage(
            $this->getModuleLabel('sites.domainActivated.body', [htmlspecialchars($domain)]),
            $this->getModuleLabel('sites.domainActivated.title'),
            Message::SEVERITY_OK,
            [],
            1412373539
        );
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, ['site' => $domain->getSite()]);
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
        $this->addFlashMessage(
            $this->getModuleLabel('sites.domainDeactivated.body', [htmlspecialchars($domain)]),
            $this->getModuleLabel('sites.domainDeactivated.title'),
            Message::SEVERITY_OK,
            [],
            1412373425
        );
        $this->unsetLastVisitedNodeAndRedirect('edit', null, null, ['site' => $domain->getSite()]);
    }

    /**
     * @param string $actionName Name of the action to forward to
     * @param string $controllerName Unqualified object name of the controller to forward to.
 *                                   If not specified, the current controller is used.
     * @param string $packageKey Key of the package containing the controller to forward to.
     *                           If not specified, the current package is assumed.
     * @param array<string,mixed> $arguments Array of arguments for the target action
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @param string $format The format to use for the redirect URI
     * @return void
     */
    protected function unsetLastVisitedNodeAndRedirect(
        $actionName,
        $controllerName = null,
        $packageKey = null,
        array $arguments = [],
        $delay = 0,
        $statusCode = 303,
        $format = null
    ) {
        $this->session->putData('lastVisitedNode', null);
        parent::redirect($actionName, $controllerName, $packageKey, $arguments, $delay, $statusCode, $format);
    }
}
