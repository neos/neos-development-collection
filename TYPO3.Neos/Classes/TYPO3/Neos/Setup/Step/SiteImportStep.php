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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Setup\Exception as SetupException;
use TYPO3\Flow\Error\Message;

/**
 * @Flow\Scope("singleton")
 */
class SiteImportStep extends \TYPO3\Setup\Step\AbstractStep
{
    /**
     * @var boolean
     */
    protected $optional = true;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Package\PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Domain\Repository\SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Domain\Service\SiteImportService
     */
    protected $siteImportService;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Domain\Repository\DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Mvc\FlashMessageContainer
     */
    protected $flashMessageContainer;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\Form\Finishers\ClosureFinisher
     */
    protected $closureFinisher;

    /**
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     * @Flow\Inject
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * Returns the form definitions for the step
     *
     * @param \TYPO3\Form\Core\Model\FormDefinition $formDefinition
     * @return void
     */
    protected function buildForm(\TYPO3\Form\Core\Model\FormDefinition $formDefinition)
    {
        $page1 = $formDefinition->createPage('page1');
        $page1->setRenderingOption('header', 'Create a new site');

        $introduction = $page1->createElement('introduction', 'TYPO3.Form:StaticText');
        $introduction->setProperty('text', 'There are two ways of creating a site. Choose between the following:');

        $importSection = $page1->createElement('import', 'TYPO3.Form:Section');
        $importSection->setLabel('Import a site from an existing site package');

        $sitePackages = array();
        foreach ($this->packageManager->getFilteredPackages('available', null, 'typo3-flow-site') as $package) {
            $sitePackages[$package->getPackageKey()] = $package->getPackageKey();
        }

        if (count($sitePackages) > 0) {
            $site = $importSection->createElement('site', 'TYPO3.Form:SingleSelectDropdown');
            $site->setLabel('Select a site package');
            $site->setProperty('options', $sitePackages);
            $site->addValidator(new \TYPO3\Flow\Validation\Validator\NotEmptyValidator());

            $sites = $this->siteRepository->findAll();
            if ($sites->count() > 0) {
                $prune = $importSection->createElement('prune', 'TYPO3.Form:Checkbox');
                $prune->setLabel('Delete existing sites');
            }
        } else {
            $error = $importSection->createElement('noSitePackagesError', 'TYPO3.Form:StaticText');
            $error->setProperty('text', 'No site packages were available, make sure you have an active site package');
            $error->setProperty('elementClassAttribute', 'alert alert-warning');
        }

        if ($this->packageManager->isPackageActive('TYPO3.Neos.Kickstarter')) {
            $separator = $page1->createElement('separator', 'TYPO3.Form:StaticText');
            $separator->setProperty('elementClassAttribute', 'section-separator');

            $newPackageSection = $page1->createElement('newPackageSection', 'TYPO3.Form:Section');
            $newPackageSection->setLabel('Create a new site package with a dummy site');
            $packageName = $newPackageSection->createElement('packageKey', 'TYPO3.Form:SingleLineText');
            $packageName->setLabel('Package Name (in form "Vendor.DomainCom")');
            $packageName->addValidator(new \TYPO3\Neos\Validation\Validator\PackageKeyValidator());

            $siteName = $newPackageSection->createElement('siteName', 'TYPO3.Form:SingleLineText');
            $siteName->setLabel('Site Name (e.g. "domain.com")');
        } else {
            $error = $importSection->createElement('neosKickstarterUnavailableError', 'TYPO3.Form:StaticText');
            $error->setProperty('text', 'The Neos Kickstarter package (TYPO3.Neos.Kickstarter) is not installed, install it for kickstarting new sites (using "composer require typo3/neos-kickstarter")');
            $error->setProperty('elementClassAttribute', 'alert alert-warning');
        }

        $explanation = $page1->createElement('explanation', 'TYPO3.Form:StaticText');
        $explanation->setProperty('text', 'Notice the difference between a site package and a site. A site package is a Flow package that can be used for creating multiple site instances.');
        $explanation->setProperty('elementClassAttribute', 'alert alert-info');

        $step = $this;
        $callback = function (\TYPO3\Form\Core\Model\FinisherContext $finisherContext) use ($step) {
            $step->importSite($finisherContext);
        };
        $this->closureFinisher = new \TYPO3\Form\Finishers\ClosureFinisher();
        $this->closureFinisher->setOption('closure', $callback);
        $formDefinition->addFinisher($this->closureFinisher);

        $formDefinition->setRenderingOption('skipStepNotice', 'You can always import a site using the site:import command');
    }

    /**
     * @param \TYPO3\Form\Core\Model\FinisherContext $finisherContext
     * @return void
     * @throws \TYPO3\Setup\Exception
     */
    public function importSite(\TYPO3\Form\Core\Model\FinisherContext $finisherContext)
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
                throw new \TYPO3\Setup\Exception(sprintf('The package key "%s" already exists.', $formValues['packageKey']), 1346759486);
            }
            $packageKey = $formValues['packageKey'];
            $siteName = $formValues['siteName'];

            $generatorService = $this->objectManager->get('TYPO3\Neos\Kickstarter\Service\GeneratorService');
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
        $sitePackagesToDeactivate = $this->packageManager->getFilteredPackages('active', null, 'typo3-flow-site');
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
