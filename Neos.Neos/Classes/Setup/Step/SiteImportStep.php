<?php
namespace Neos\Neos\Setup\Step;

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
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\FlashMessageContainer;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Validation\Validator\NotEmptyValidator;
use Neos\Form\Core\Model\FinisherContext;
use Neos\Form\Core\Model\FormDefinition;
use Neos\Form\Finishers\ClosureFinisher;
use Neos\Form\FormElements\Section;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\Validation\Validator\PackageKeyValidator;
use Neos\Setup\Exception as SetupException;
use Neos\Setup\Exception;
use Neos\Setup\Step\AbstractStep;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\SiteKickstarter\Service\GeneratorService;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class SiteImportStep extends AbstractStep
{
    /**
     * @var boolean
     */
    protected $optional = true;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var SiteImportService
     */
    protected $siteImportService;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var FlashMessageContainer
     */
    protected $flashMessageContainer;

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
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ClosureFinisher
     */
    protected $closureFinisher;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ThrowableStorageInterface
     */
    private $throwableStorage;

    /**
     * @param LoggerInterface $logger
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ThrowableStorageInterface $throwableStorage
     */
    public function injectThrowableStorage(ThrowableStorageInterface $throwableStorage)
    {
        $this->throwableStorage = $throwableStorage;
    }

    /**
     * Returns the form definitions for the step
     *
     * @param FormDefinition $formDefinition
     * @return void
     */
    protected function buildForm(FormDefinition $formDefinition)
    {
        $page1 = $formDefinition->createPage('page1');
        $page1->setRenderingOption('header', 'Create a new site');

        $introduction = $page1->createElement('introduction', 'Neos.Form:StaticText');
        $introduction->setProperty('text', 'There are two ways of creating a site. Choose between the following:');

        $importSection = $page1->createElement('import', 'Neos.Form:Section');
        /** @var Section $importSection */
        $importSection->setLabel('Import a site from an existing site package');

        $sitePackages = [];
        foreach ($this->packageManager->getFilteredPackages('available', null, 'neos-site') as $package) {
            /** @var PackageInterface $package */
            $sitePackages[$package->getPackageKey()] = $package->getPackageKey();
        }

        if (count($sitePackages) > 0) {
            $site = $importSection->createElement('site', 'Neos.Form:SingleSelectDropdown');
            $site->setLabel('Select a site package');
            $site->setProperty('options', $sitePackages);
            $site->addValidator(new NotEmptyValidator());

            $sites = $this->siteRepository->findAll();
            if ($sites->count() > 0) {
                $prune = $importSection->createElement('prune', 'Neos.Form:Checkbox');
                $prune->setLabel('Delete existing sites');
            }
        } else {
            $error = $importSection->createElement('noSitePackagesError', 'Neos.Form:StaticText');
            $error->setProperty('text', 'No site packages were available, make sure you have an active site package');
            $error->setProperty('elementClassAttribute', 'alert alert-warning');
        }

        if ($this->packageManager->isPackageAvailable('Neos.SiteKickstarter')) {
            $separator = $page1->createElement('separator', 'Neos.Form:StaticText');
            $separator->setProperty('elementClassAttribute', 'section-separator');

            $newPackageSection = $page1->createElement('newPackageSection', 'Neos.Form:Section');
            /** @var Section $newPackageSection */
            $newPackageSection->setLabel('Create a new site package with a dummy site');
            $packageName = $newPackageSection->createElement('packageKey', 'Neos.Form:SingleLineText');
            $packageName->setLabel('Package Name (in form "Vendor.DomainCom")');
            $packageName->addValidator(new PackageKeyValidator());

            $siteName = $newPackageSection->createElement('siteName', 'Neos.Form:SingleLineText');
            $siteName->setLabel('Site Name (e.g. "domain.com")');
        } else {
            $error = $importSection->createElement('neosKickstarterUnavailableError', 'Neos.Form:StaticText');
            $error->setProperty('text', 'The Neos Kickstarter package (Neos.SiteKickstarter) is not installed, install it for kickstarting new sites (using "composer require typo3/neos-kickstarter")');
            $error->setProperty('elementClassAttribute', 'alert alert-warning');
        }

        $sitePackageExplanation = $page1->createElement('sitePackageExplanation', 'Neos.Form:StaticText');
        $sitePackageExplanation->setProperty('text', 'Notice the difference between a site package and a site. A site package is a Flow package that can be used for creating multiple site instances.');
        $sitePackageExplanation->setProperty('elementClassAttribute', 'alert alert-info');

        if (count($sitePackages) > 0) {
            $sitePackageAlreadyAvailableExplanation = $page1->createElement('sitePackageAlreadyAvailableExplanation', 'Neos.Form:StaticText');
            $sitePackageAlreadyAvailableExplanation->setProperty('text', sprintf('There are already other site packages available (%s). Some configuration like dimensions and node type configurations are shared between all sites packages. Make sure you remove the site packages you don\'t want to interfere with your newly created package.', implode(array_keys($sitePackages))));
            $sitePackageAlreadyAvailableExplanation->setProperty('elementClassAttribute', 'alert alert-info');
        }

        $step = $this;
        $callback = function (FinisherContext $finisherContext) use ($step) {
            $step->importSite($finisherContext);
        };
        $this->closureFinisher = new ClosureFinisher();
        $this->closureFinisher->setOption('closure', $callback);
        $formDefinition->addFinisher($this->closureFinisher);

        $formDefinition->setRenderingOption('skipStepNotice', 'You can always import a site using the site:import command');
    }

    /**
     * @param FinisherContext $finisherContext
     * @return void
     * @throws Exception
     */
    public function importSite(FinisherContext $finisherContext)
    {
        $formValues = $finisherContext->getFormRuntime()->getFormState()->getFormValues();

        if (isset($formValues['prune']) && intval($formValues['prune']) === 1) {
            $this->nodeDataRepository->removeAll();
            $this->workspaceRepository->removeAll();
            $this->domainRepository->removeAll();
            $this->siteRepository->removeAll();
            $this->persistenceManager->persistAll();
        }

        if (!empty($formValues['packageKey'])) {
            if ($this->packageManager->isPackageAvailable($formValues['packageKey'])) {
                throw new Exception(sprintf('The package key "%s" already exists.', $formValues['packageKey']), 1346759486);
            }
            $packageKey = $formValues['packageKey'];
            $siteName = $formValues['siteName'];

            $generatorService = $this->objectManager->get(GeneratorService::class);
            $generatorService->generateSitePackage($packageKey, $siteName);
        } elseif (!empty($formValues['site'])) {
            $packageKey = $formValues['site'];
        }

        if (!empty($packageKey)) {
            try {
                $this->siteImportService->importFromPackage($packageKey);
            } catch (\Exception $exception) {
                $finisherContext->cancel();
                $logMessage = $this->throwableStorage->logThrowable($exception);
                $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
                throw new SetupException(sprintf('Error: During the import of the "Sites.xml" from the package "%s" an exception occurred: %s', $packageKey, $exception->getMessage()), 1351000864);
            }
        }
    }
}
