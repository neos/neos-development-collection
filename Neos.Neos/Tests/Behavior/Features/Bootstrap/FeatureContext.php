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

use Behat\Behat\Definition\Call\Then;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\MinkContext;
use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\EventSourcedTrait;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\ContentRepositoryInternalsFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeClockFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeUserIdProviderFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\MigrationsTrait;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\Security\Service\AuthorizationService;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Utility\Environment;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\SiteExportService;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Tests\Functional\Command\BehatTestHelper;
use Neos\Party\Domain\Repository\PartyRepository;
use Neos\Utility\Arrays;
use Neos\Utility\Files;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Assert;

require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentSubgraphTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentUserTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentDateTimeTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/GenericCommandExecutionAndEventPublication.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/ProjectedNodeAggregateTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/ProjectedNodeTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/MigrationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Security/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentGraph.DoctrineDbalAdapter/Tests/Behavior/Features/Bootstrap/ProjectionIntegrityViolationDetectionTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/StructureAdjustmentsTrait.php');

require_once(__DIR__ . '/../../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/NodeTraversalTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/EventSourcedTrait.php');

require_once(__DIR__ . '/HistoryDefinitionsTrait.php');

/**
 * Features context
 */
class FeatureContext extends MinkContext
{
    use FlowContextTrait;
    use BrowserTrait;
    use NodeAuthorizationTrait;
    use SecurityOperationsTrait;
    use IsolatedBehatStepsTrait;
    use HistoryDefinitionsTrait;
    use NodeOperationsTrait;

    use EventSourcedTrait;
    use RoutingTrait;
    use MigrationsTrait;


    /**
     * @var string
     */
    protected $behatTestHelperObjectName = BehatTestHelper::class;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ElementInterface
     */
    protected $selectedContentElement;

    /**
     * @var string
     */
    protected $lastExportedSiteXmlPathAndFilename = '';

    /**
     * @var Environment
     */
    protected $environment;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();
        $this->environment = $this->objectManager->get(Environment::class);

        $this->nodeAuthorizationService = $this->objectManager->get(AuthorizationService::class);
        $this->setupSecurity();
        $this->setupEventSourcedTrait(true);
    }

    protected function initCleanContentRepository(array $adapterKeys): void
    {
        $this->logToRaceConditionTracker(['msg' => 'initCleanContentRepository']);

        $configurationManager = $this->getObjectManager()->get(ConfigurationManager::class);
        $registrySettings = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.ContentRepositoryRegistry'
        );

        unset($registrySettings['presets'][$this->contentRepositoryId->value]['projections']['Neos.ContentGraph.PostgreSQLAdapter:Hypergraph']);
        $registrySettings['presets'][$this->contentRepositoryId->value]['userIdProvider']['factoryObjectName'] = FakeUserIdProviderFactory::class;
        $registrySettings['presets'][$this->contentRepositoryId->value]['clock']['factoryObjectName'] = FakeClockFactory::class;

        $this->contentRepositoryRegistry = new ContentRepositoryRegistry(
            $registrySettings,
            $this->getObjectManager()
        );


        $this->contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryId);
        // Big performance optimization: only run the setup once - DRAMATICALLY reduces test time
        if ($this->alwaysRunContentRepositorySetup || !self::$wasContentRepositorySetupCalled) {
            $this->contentRepository->setUp();
            self::$wasContentRepositorySetupCalled = true;
        }
        $this->contentRepositoryInternals = $this->contentRepositoryRegistry->getService(
            $this->contentRepositoryId,
            new ContentRepositoryInternalsFactory()
        );

        $availableContentGraphs = [];
        $availableContentGraphs['DoctrineDBAL'] = $this->contentRepository->getContentGraph();

        if (count($availableContentGraphs) === 0) {
            throw new \RuntimeException('No content graph active during testing. Please set one in settings in activeContentGraphs');
        }
        $this->availableContentGraphs = new ContentGraphs($availableContentGraphs);
    }

    protected function getContentRepositoryService(
        ContentRepositoryId $contentRepositoryId,
        ContentRepositoryServiceFactoryInterface $factory
    ): ContentRepositoryServiceInterface {
        /** @var ContentRepositoryRegistry $contentRepositoryRegistry */
        $contentRepositoryRegistry = $this->contentRepositoryRegistry
            ?: $this->objectManager->get(ContentRepositoryRegistry::class);

        return $contentRepositoryRegistry->getService(
            $contentRepositoryId,
            $factory
        );
    }

    protected function getDbalClient(): DbalClientInterface
    {
        return $this->dbalClient;
    }


    protected function getContentRepositoryRegistry(): \Neos\ContentRepositoryRegistry\ContentRepositoryRegistry
    {
        /** @var ContentRepositoryRegistry $contentRepositoryRegistry */
        $contentRepositoryRegistry = $this->objectManager->get(ContentRepositoryRegistry::class);

        return $contentRepositoryRegistry;
    }

    /**
     * @Given /^I am not authenticated$/
     */
    public function iAmNotAuthenticated()
    {
        // Do nothing, every scenario has a new session
    }

    /**
     * @Then /^I should see a login form$/
     */
    public function iShouldSeeALoginForm()
    {
        $this->assertSession()->fieldExists('Username');
        $this->assertSession()->fieldExists('Password');
    }

    /**
     * @Given /^the following users exist:$/
     */
    public function theFollowingUsersExist(TableNode $table)
    {
        $rows = $table->getHash();
        /** @var UserService $userService */
        $userService = $this->objectManager->get(UserService::class);
        /** @var PartyRepository $partyRepository */
        $partyRepository = $this->objectManager->get(PartyRepository::class);
        /** @var AccountRepository $accountRepository */
        $accountRepository = $this->objectManager->get(AccountRepository::class);
        foreach ($rows as $row) {
            $roleIdentifiers = array_map(function ($role) {
                return 'Neos.Neos:' . $role;
            }, Arrays::trimExplode(',', $row['roles']));
            $userService->createUser($row['username'], $row['password'], $row['firstname'], $row['lastname'], $roleIdentifiers);
        }
        $this->persistAll();
    }

    /**
     * @Given /^I am authenticated with "([^"]*)" and "([^"]*)" for the backend$/
     */
    public function iAmAuthenticatedWithAndForTheBackend($username, $password)
    {
        $this->visit('/');
        $this->fillField('Username', $username);
        $this->fillField('Password', $password);
        $this->pressButton('Login');
    }

    /**
     * @Then /^I should be on the "([^"]*)" page$/
     */
    public function iShouldBeOnThePage($page)
    {
        switch ($page) {
            case 'Login':
                $this->assertSession()->addressEquals('/neos/login');
                break;
            default:
                throw new PendingException();
        }
    }

    /**
     * @Then /^I should be in the "([^"]*)" module$/
     */
    public function iShouldBeInTheModule($moduleName)
    {
        switch ($moduleName) {
            case 'Content':
                $this->assertSession()->addressMatches('/^\/(?!neos).*@.+$/');
                break;
            default:
                throw new PendingException();
        }
    }

    /**
     * @When /^I follow "([^"]*)" in the main menu$/
     */
    public function iFollowInTheMainMenu($link)
    {
        $this->assertElementOnPage('ul.nav');
        $this->getSession()->getPage()->find('css', 'ul.nav')->findLink($link)->click();
    }

    /**
     * @Given /^I should be logged in as "([^"]*)"$/
     */
    public function iShouldBeLoggedInAs($name)
    {
        $this->assertSession()->elementTextContains('css', '#neos-user-actions .neos-user-menu', $name);
    }

    /**
     * @Then /^I should not be logged in$/
     */
    public function iShouldNotBeLoggedIn()
    {
        if ($this->getSession()->getPage()->findButton('logout')) {
            Assert::fail('"Logout" Button not expected');
        }
    }

    /**
     * @Given /^I should see the page title "([^"]*)"$/
     */
    public function iShouldSeeThePageTitle($title)
    {
        $this->assertSession()->elementTextContains('css', 'title', $title);
    }

    /**
     * @Then /^I should not see the top bar$/
     */
    public function iShouldNotSeeTheTopBar()
    {
        return [
            new Then('I should not see "Navigate"'),
            new Then('I should not see "Edit / Preview"'),
        ];
        //c1$this->assertElementOnPage('.neos-previewmode #neos-top-bar');
    }

    /**
     * @Given /^the Previewbutton should be active$/
     */
    public function thePreviewButtonShouldBeActive()
    {
        $button = $this->getSession()->getPage()->find('css', '.neos-full-screen-close > .neos-pressed');
        if ($button === null) {
            throw new ElementNotFoundException($this->getSession(), 'button', 'id|name|label|value');
        }

        Assert::assertTrue($button->hasClass('neos-pressed'), 'Button should be pressed');
    }

    /**
     * @Given /^I imported the site "([^"]*)"$/
     */
    public function iImportedTheSite($packageKey)
    {
        /** @var NodeDataRepository $nodeDataRepository */
        $nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        /** @var ContextFactoryInterface $contextFactory */
        $contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contentContext = $contextFactory->create(['workspace' => 'live']);
        ObjectAccess::setProperty($nodeDataRepository, 'context', $contentContext, true);

        /** @var SiteImportService $siteImportService */
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromPackage($packageKey);
        $this->persistAll();
    }

    /**
     * @When /^I go to the "([^"]*)" module$/
     */
    public function iGoToTheModule($module)
    {
        switch ($module) {
            case 'Administration / Site Management':
                $this->visit('/neos/administration/sites');
                break;
            case 'Administration / User Management':
                $this->visit('/neos/administration/users');
                break;
            default:
                throw new PendingException();
        }
    }

    /**
     * Clear the content cache. Since this could be needed for multiple Flow contexts, we have to do it on the
     * filesystem for now. Using a different cache backend than the FileBackend will not be possible with this approach.
     *
     * @BeforeScenario @fixtures
     */
    public function clearContentCache()
    {
        $directories = array_merge(
            glob(FLOW_PATH_DATA . 'Temporary/*/Cache/Data/Neos_Fusion_Content'),
            glob(FLOW_PATH_DATA . 'Temporary/*/*/Cache/Data/Neos_Fusion_Content')
        );
        if (is_array($directories)) {
            foreach ($directories as $directory) {
                Files::removeDirectoryRecursively($directory);
            }
        }
    }

    /**
     * @BeforeScenario @fixtures
     */
    public function removeTestSitePackages()
    {
        $directories = glob(FLOW_PATH_PACKAGES . 'Sites/Test.*');
        if (is_array($directories)) {
            foreach ($directories as $directory) {
                Files::removeDirectoryRecursively($directory);
            }
        }
    }

    /**
     * @BeforeScenario
     */
    public function resetPersistenceManagerAndFeedbackCollection()
    {
        // FIXME: we have some strange race condition between the scenarios; my theory is that
        // somehow projectors still run in the background when we start from scratch...
        sleep(2);
        $this->getObjectManager()->get(\Neos\Flow\Persistence\PersistenceManagerInterface::class)->clearState();
        // FIXME: FeedbackCollection is a really ugly, hacky SINGLETON; so it needs to be RESET!
        $this->getObjectManager()->get(\Neos\Neos\Ui\Domain\Model\FeedbackCollection::class)->reset();

        // The UserService has a runtime cache - which we need to reset as well as our users get new IDs.
        // Did I already mention I LOVE in memory caches? ;-) ;-) ;-)
        $userService = $this->getObjectManager()->get(\Neos\Neos\Domain\Service\UserService::class);
        \Neos\Utility\ObjectAccess::setProperty($userService, 'runtimeUserCache', [], true);
    }
    /**
     * @Then /^I should see the following sites in a table:$/
     */
    public function iShouldSeeTheFollowingSitesInATable(TableNode $table)
    {
        $sites = $table->getHash();

        $tableLocator = '.neos-module-wrap table.neos-table';
        $sitesTable = $this->assertSession()->elementExists('css', $tableLocator);

        $siteRows = $sitesTable->findAll('css', 'tbody tr');
        $actualSites = array_map(function ($row) {
            $firstColumn = $row->find('css', 'td:nth-of-type(1)');
            if ($firstColumn !== null) {
                return [
                    'name' => $firstColumn->getText()
                ];
            }
        }, $siteRows);

        $sortByName = function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        };
        usort($sites, $sortByName);
        usort($actualSites, $sortByName);

        Assert::assertEquals($sites, $actualSites);
    }

    /**
     * @Given /^I follow "([^"]*)" for site "([^"]*)"$/
     */
    public function iFollowForSite($link, $siteName)
    {
        $rowLocator = sprintf("//table[@class='neos-table']//tr[td/text()='%s']", $siteName);
        $siteRow = $this->assertSession()->elementExists('xpath', $rowLocator);
        $siteRow->findLink($link)->click();
    }

    /**
     * @When /^I select the first content element$/
     */
    public function iSelectTheFirstContentElement()
    {
        $element = $this->assertSession()->elementExists('css', '.neos-contentelement');
        $element->click();

        $this->selectedContentElement = $element;
    }

    /**
     * @When /^I select the first headline content element$/
     */
    public function iSelectTheFirstHeadlineContentElement()
    {
        $element = $this->assertSession()->elementExists('css', '.neos-nodetypes-headline');
        $element->click();

        $this->selectedContentElement = $element;
    }

    /**
     * @Given /^I set the content to "([^"]*)"$/
     */
    public function iSetTheContentTo($content)
    {
        $editable = $this->assertSession()->elementExists('css', '[data-neos-editable-node-contextpath]', $this->selectedContentElement);

        $this->spinWait(function () use ($editable) {
            return $editable->hasAttribute('contenteditable');
        }, 10000, 'editable has contenteditable attribute set');

        $editable->setValue($content);
    }

    /**
     * @Given /^I wait for the changes to be saved$/
     */
    public function iWaitForTheChangesToBeSaved()
    {
        $this->getSession()->wait(30000, '$(".neos-indicator-saved").length > 0');
        $this->assertSession()->elementExists('css', '.neos-indicator-saved');
    }

    /**
     * @param string $elementName
     * @return string
     */
    protected function getNamedElementSelector($elementName)
    {
        switch ($elementName) {
            case 'Open full screen':
                return '.neos-full-screen-open';
            case 'Close full screen':
                return '.neos-full-screen-close';
            default:
                Assert::fail('No element definition found for named element "' . $elementName . '"');
        }
    }

    /**
     * @When /^I wait for the "([^"]*)"( button) to be visible$/
     */
    public function iWaitForElement($elementName)
    {
        $elementSelector = $this->getNamedElementSelector($elementName);

        $this->getSession()->wait(30000, '$("' . $elementSelector . '").length > 0');
        $this->assertSession()->elementExists('css', $elementSelector);
    }

    /**
     * @param string $path
     * @return string
     */
    public function locatePath($path)
    {
        return parent::locatePath($this->resolvePath($path));
    }

    /**
     * @param callable $callback
     * @param integer $timeout Timeout in milliseconds
     * @param string $message
     */
    public function spinWait($callback, $timeout, $message = '')
    {
        $waited = 0;
        while ($callback() !== true) {
            if ($waited > $timeout) {
                Assert::fail($message);
                return;
            }
            usleep(50000);
            $waited += 50;
        }
    }

    /**
     * @Given /^I have the site "([^"]*)"$/
     */
    public function iHaveTheSite($siteName)
    {
        $site = new Site($siteName);
        $site->setSiteResourcesPackageKey('Neos.Demo');
        /** @var SiteRepository $siteRepository */
        $siteRepository = $this->objectManager->get(SiteRepository::class);
        $siteRepository->add($site);

        $this->persistAll();
    }

    /**
     * @When /^I export the site "([^"]*)"$/
     */
    public function iExportTheSite($siteNodeName)
    {
        /** @var SiteExportService $siteExportService */
        $siteExportService = $this->objectManager->get(SiteExportService::class);

        /** @var SiteRepository $siteRepository */
        $siteRepository = $this->objectManager->get(SiteRepository::class);
        $site = $siteRepository->findOneByNodeName($siteNodeName);

        $this->lastExportedSiteXmlPathAndFilename = tempnam(sys_get_temp_dir(), 'Neos_LastExportedSite');

        file_put_contents($this->lastExportedSiteXmlPathAndFilename, $siteExportService->export([$site]));
    }

    /**
     * @When /^I prune all sites$/
     */
    public function iPruneAllSites()
    {
        /** @var SiteService $siteService */
        $siteService = $this->objectManager->get(SiteService::class);
        $siteService->pruneAll();

        $this->persistAll();
    }

    /**
     * @When /^I import the last exported site$/
     */
    public function iImportTheLastExportedSite()
    {
        // Persist any pending entity insertions (caused by lazy creation of live Workspace)
        // This is a workaround which should be solved by properly isolating all read-only steps
        $this->persistAll();
        $this->resetNodeInstances();

        /** @var SiteImportService $siteImportService */
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile($this->lastExportedSiteXmlPathAndFilename);
    }
}
