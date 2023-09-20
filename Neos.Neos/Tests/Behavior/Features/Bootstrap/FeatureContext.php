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

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinPyStringNodeBasedNodeTypeManagerFactory;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinTableNodeBasedContentDimensionSourceFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\MigrationsTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
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
use Behat\Behat\Context\Context as BehatContext;


require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Security/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentGraph.DoctrineDbalAdapter/Tests/Behavior/Features/Bootstrap/ProjectionIntegrityViolationDetectionTrait.php');

require_once(__DIR__ . '/../../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

require_once(__DIR__ . '/HistoryDefinitionsTrait.php');

class FeatureContext implements BehatContext
{
    use FlowContextTrait;
    use BrowserTrait;
    use SecurityOperationsTrait;
    use IsolatedBehatStepsTrait;
    use HistoryDefinitionsTrait;

    use CRTestSuiteTrait;
    use CRBehavioralTestsSubjectProvider;
    use RoutingTrait;
    use MigrationsTrait;

    protected string $behatTestHelperObjectName = BehatTestHelper::class;

    protected string $lastExportedSiteXmlPathAndFilename = '';

    protected Environment $environment;

    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();
        $this->environment = $this->objectManager->get(Environment::class);
        $this->contentRepositoryRegistry = $this->objectManager->get(ContentRepositoryRegistry::class);

        $this->setupSecurity();
        $this->setupCRTestSuiteTrait(true);
    }

    /**
     * @BeforeScenario
     */
    public function resetContentRepositoryComponents(BeforeScenarioScope $scope): void
    {
        GherkinTableNodeBasedContentDimensionSourceFactory::reset();
        GherkinPyStringNodeBasedNodeTypeManagerFactory::reset();
    }

    /**
     * @Given /^I am not authenticated$/
     */
    public function iAmNotAuthenticated()
    {
        // Do nothing, every scenario has a new session
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

    protected function getContentRepositoryService(
        ContentRepositoryServiceFactoryInterface $factory
    ): ContentRepositoryServiceInterface {
        return $this->contentRepositoryRegistry->buildService(
            $this->currentContentRepository->id,
            $factory
        );
    }

    protected function createContentRepository(
        ContentRepositoryId $contentRepositoryId
    ): ContentRepository {
        $this->contentRepositoryRegistry->resetFactoryInstance($contentRepositoryId);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        GherkinTableNodeBasedContentDimensionSourceFactory::reset();
        GherkinPyStringNodeBasedNodeTypeManagerFactory::reset();

        return $contentRepository;
    }
}
