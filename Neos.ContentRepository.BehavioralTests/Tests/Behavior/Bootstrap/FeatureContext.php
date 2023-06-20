<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/ContentStreamForking.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeCopying.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeCreation.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeDisabling.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeModification.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeMove.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeReferencing.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeRemoval.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeRenaming.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeTypeChange.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeVariation.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/WorkspaceCreation.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/WorkspaceDiscarding.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/WorkspacePublishing.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentSubgraphTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentUserTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentDateTimeTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/GenericCommandExecutionAndEventPublication.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/NodeTraversalTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/ProjectedNodeAggregateTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/ProjectedNodeTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/EventSourcedTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/MigrationsTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Security/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentGraph.DoctrineDbalAdapter/Tests/Behavior/Features/Bootstrap/ProjectionIntegrityViolationDetectionTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/StructureAdjustmentsTrait.php');
require_once(__DIR__ . '/../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\RedisInterleavingLogger;
use Neos\ContentRepository\BehavioralTests\Tests\Functional\BehatTestHelper;
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
use Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap\ProjectionIntegrityViolationDetectionTrait;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\StructureAdjustmentsTrait;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\Security\Service\AuthorizationService;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\CatchUpTriggerWithSynchronousOption;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Utility\Environment;

/**
 * Features context
 */
class FeatureContext implements \Behat\Behat\Context\Context
{
    use FlowContextTrait;
    use NodeOperationsTrait;
    use NodeAuthorizationTrait;
    use SecurityOperationsTrait;
    use IsolatedBehatStepsTrait;
    use EventSourcedTrait;
    use ProjectionIntegrityViolationDetectionTrait;
    use StructureAdjustmentsTrait;
    use MigrationsTrait;

    /**
     * @var string
     */
    protected $behatTestHelperObjectName = BehatTestHelper::class;

    protected ?ContentRepositoryRegistry $contentRepositoryRegistry = null;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();

        $this->setupSecurity();
        $this->nodeAuthorizationService = $this->getObjectManager()->get(AuthorizationService::class);
        $this->dbalClient = $this->getObjectManager()->get(DbalClientInterface::class);
        $this->setupEventSourcedTrait();
        if (getenv('CATCHUPTRIGGER_ENABLE_SYNCHRONOUS_OPTION')) {
            CatchUpTriggerWithSynchronousOption::enableSynchonityForSpeedingUpTesting();
        }
        $this->setUpInterleavingLogger();
    }

    private function setUpInterleavingLogger(): void
    {
        // prepare race tracking for debugging into the race log
        if (class_exists(RedisInterleavingLogger::class)) { // the class must exist (the package loaded)
            $raceConditionTrackerConfig = $this->getObjectManager()->get(ConfigurationManager::class)
                ->getConfiguration(
                    ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                    'Neos.ContentRepository.BehavioralTests.raceConditionTracker'
                );

            // if it's enabled, correctly configure the Redis connection.
            // Then, people can use {@see logToRaceConditionTracker()} for debugging.
            $this->raceConditionTrackerEnabled = boolval($raceConditionTrackerConfig['enabled']);
            if ($this->raceConditionTrackerEnabled) {
                RedisInterleavingLogger::connect(
                    $raceConditionTrackerConfig['redis']['host'],
                    $raceConditionTrackerConfig['redis']['port']
                );
            }
        }
    }


    /**
     * @param array<string> $adapterKeys "DoctrineDBAL" if
     * @return void
     */
    protected function initCleanContentRepository(array $adapterKeys): void
    {
        $this->logToRaceConditionTracker(['msg' => 'initCleanContentRepository']);

        $configurationManager = $this->getObjectManager()->get(ConfigurationManager::class);
        $registrySettings = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.ContentRepositoryRegistry'
        );

        if (!in_array('Postgres', $adapterKeys)) {
            // in case we do not have tests annotated with @adapters=Postgres, we
            // REMOVE the Postgres projection from the Registry settings. This way, we won't trigger
            // Postgres projection catchup for tests which are not yet postgres-aware.
            //
            // This is to make the testcases more stable and deterministic. We can remove this workaround
            // once the Postgres adapter is fully ready.
            unset($registrySettings['presets'][$this->contentRepositoryId->value]['projections']['Neos.ContentGraph.PostgreSQLAdapter:Hypergraph']);
        }
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
        $this->contentRepositoryInternals = $this->contentRepositoryRegistry->getService($this->contentRepositoryId, new ContentRepositoryInternalsFactory());

        $availableContentGraphs = [];
        $availableContentGraphs['DoctrineDBAL'] = $this->contentRepository->getContentGraph();
        // NOTE: to disable a content graph (do not run the tests for it), you can use "null" as value.
        if (in_array('Postgres', $adapterKeys)) {
            $availableContentGraphs['Postgres'] = $this->contentRepository->projectionState(ContentHypergraph::class);
        }

        if (count($availableContentGraphs) === 0) {
            throw new \RuntimeException('No content graph active during testing. Please set one in settings in activeContentGraphs');
        }
        $this->availableContentGraphs = new ContentGraphs($availableContentGraphs);
    }

    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }

    /**
     * @return Environment
     */
    protected function getEnvironment()
    {
        return $this->objectManager->get(Environment::class);
    }

    protected function getContentRepositoryService(ContentRepositoryId $contentRepositoryId, ContentRepositoryServiceFactoryInterface $factory): ContentRepositoryServiceInterface
    {
        return $this->getContentRepositoryRegistry()->getService($contentRepositoryId, $factory);
    }

    protected function getDbalClient(): DbalClientInterface
    {
        return $this->dbalClient;
    }

    protected function getContentRepositoryRegistry(): ContentRepositoryRegistry
    {
        /** @var ContentRepositoryRegistry $contentRepositoryRegistry */
        $contentRepositoryRegistry = $this->objectManager->get(ContentRepositoryRegistry::class);

        return $contentRepositoryRegistry;
    }
}
