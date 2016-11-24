<?php
namespace TYPO3\Neos\Setup\Step;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\FlashMessageContainer;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Validation\Validator\NotEmptyValidator;
use Neos\Form\Core\Model\FinisherContext;
use Neos\Form\Core\Model\FormDefinition;
use Neos\Form\Finishers\ClosureFinisher;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\Neos\Validation\Validator\PackageKeyValidator;
use Neos\Setup\Exception as SetupException;
use Neos\Error\Messages\Message;
use Neos\Setup\Exception;
use Neos\Setup\Step\AbstractStep;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

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
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

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
        $importSection->setLabel('Import a site from an existing site package');

        $sitePackages = array();
        foreach ($this->packageManager->getFilteredPackages('available', null, 'neos-site') as $package) {
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

        if ($this->packageManager->isPackageActive('TYPO3.Neos.Kickstarter')) {
            $separator = $page1->createElement('separator', 'Neos.Form:StaticText');
            $separator->setProperty('elementClassAttribute', 'section-separator');

            $newPackageSection = $page1->createElement('newPackageSection', 'Neos.Form:Section');
            $newPackageSection->setLabel('Create a new site package with a dummy site');
            $packageName = $newPackageSection->createElement('packageKey', 'Neos.Form:SingleLineText');
            $packageName->setLabel('Package Name (in form "Vendor.DomainCom")');
            $packageName->addValidator(new PackageKeyValidator());

            $siteName = $newPackageSection->createElement('siteName', 'Neos.Form:SingleLineText');
            $siteName->setLabel('Site Name (e.g. "domain.com")');
        } else {
            $error = $importSection->createElement('neosKickstarterUnavailableError', 'Neos.Form:StaticText');
            $error->setProperty('text', 'The Neos Kickstarter package (TYPO3.Neos.Kickstarter) is not installed, install it for kickstarting new sites (using "composer require typo3/neos-kickstarter")');
            $error->setProperty('elementClassAttribute', 'alert alert-warning');
        }

        $explanation = $page1->createElement('explanation', 'Neos.Form:StaticText');
        $explanation->setProperty('text', 'Notice the difference between a site package and a site. A site package is a Flow package that can be used for creating multiple site instances.');
        $explanation->setProperty('elementClassAttribute', 'alert alert-info');

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

            $generatorService = $this->objectManager->get(\TYPO3\Neos\Kickstarter\Service\GeneratorService::class);
            $generatorService->generateSitePackage($packageKey, $siteName);
        } elseif (!empty($formValues['site'])) {
            $packageKey = $formValues['site'];
        }

        $this->deactivateOtherSitePackages($packageKey);
        $this->packageManager->activatePackage($packageKey);

        if (!empty($packageKey)) {
            try {
                $contentContext = $this->contextFactory->create(array('workspaceName' => 'live'));
                $this->siteImportService->importFromPackage($packageKey, $contentContext);
            } catch (\Exception $exception) {
                $finisherContext->cancel();
                $this->systemLogger->logException($exception);
                throw new SetupException(sprintf('Error: During the import of the "Sites.xml" from the package "%s" an exception occurred: %s', $packageKey, $exception->getMessage()), 1351000864);
            }
        }
    }

    /**
     * If Site Packages already exist and are active, we will deactivate them in order to prevent
     * interactions with the newly created or imported package (like Content Dimensions being used).
     *
     * @param string $packageKey
     * @return array
     */
    protected function deactivateOtherSitePackages($packageKey)
    {
        $sitePackagesToDeactivate = $this->packageManager->getFilteredPackages('active', null, 'neos-site');
        $deactivatedSitePackages = array();
        foreach ($sitePackagesToDeactivate as $sitePackageToDeactivate) {
            if ($sitePackageToDeactivate->getPackageKey() !== $packageKey) {
                $this->packageManager->deactivatePackage($sitePackageToDeactivate->getPackageKey());
                $deactivatedSitePackages[] = $sitePackageToDeactivate->getPackageKey();
            }
        }

        if (count($deactivatedSitePackages) >= 1) {
            $this->flashMessageContainer->addMessage(new Message(sprintf('The existing Site Packages "%s" were deactivated, in order to prevent interactions with the newly created package "%s".', implode(', ', $deactivatedSitePackages), $packageKey)));
        }
    }
}
