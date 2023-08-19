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
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Security/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentGraph.DoctrineDbalAdapter/Tests/Behavior/Features/Bootstrap/ProjectionIntegrityViolationDetectionTrait.php');
require_once(__DIR__ . '/../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

use Behat\Behat\Context\Context as BehatContext;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\Uri;
use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntryType;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\RedisInterleavingLogger;
use Neos\ContentRepository\BehavioralTests\Tests\Functional\BehatTestHelper;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinPyStringNodeBasedNodeTypeManagerFactory;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinTableNodeBasedContentDimensionSourceFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\MigrationsTrait;
use Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap\ProjectionIntegrityViolationDetectionTrait;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\StructureAdjustmentsTrait;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PriceSpecification;
use Neos\ContentRepository\Security\Service\AuthorizationService;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\CatchUpTriggerWithSynchronousOption;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;

/**
 * Features context
 */
class FeatureContext implements BehatContext
{
    use FlowContextTrait;
    use NodeAuthorizationTrait;
    use SecurityOperationsTrait;
    use IsolatedBehatStepsTrait;
    use CRTestSuiteTrait;
    use CRBehavioralTestsSubjectProvider;
    use ProjectionIntegrityViolationDetectionTrait;
    use StructureAdjustmentsTrait;
    use MigrationsTrait;

    protected string $behatTestHelperObjectName = BehatTestHelper::class;

    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    private bool $raceConditionTrackerEnabled = false;

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
        $this->contentRepositoryRegistry = $this->objectManager->get(ContentRepositoryRegistry::class);
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
            $this->logToRaceConditionTracker(['msg' => 'setUpFeatureContext']);
        }
    }

    /**
     * This function logs a message into the race condition tracker's event log,
     * which can be inspected by calling ./flow raceConditionTracker:analyzeTrace.
     *
     * It is helpful to do this during debugging; in order to figure out whether an issue is an actual bug
     * or a situation which can only happen during test runs.
     */
    public function logToRaceConditionTracker(array $payload): void
    {
        if ($this->raceConditionTrackerEnabled) {
            RedisInterleavingLogger::trace(TraceEntryType::DebugLog, $payload);
        }
    }

    protected function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }

    protected function getContentRepositoryService(
        ContentRepositoryServiceFactoryInterface $factory
    ): ContentRepositoryServiceInterface {
        return $this->contentRepositoryRegistry->buildService(
            $this->currentContentRepository->id,
            $factory
        );
    }

    protected function deserializeProperties(array $properties): PropertyValuesToWrite
    {
        foreach ($properties as &$propertyValue) {
            if ($propertyValue === 'PostalAddress:dummy') {
                $propertyValue = PostalAddress::dummy();
            } elseif ($propertyValue === 'PostalAddress:anotherDummy') {
                $propertyValue = PostalAddress::anotherDummy();
            } elseif ($propertyValue === 'PriceSpecification:dummy') {
                $propertyValue = PriceSpecification::dummy();
            } elseif ($propertyValue === 'PriceSpecification:anotherDummy') {
                $propertyValue = PriceSpecification::anotherDummy();
            }
            if (is_string($propertyValue)) {
                if (\str_starts_with($propertyValue, 'DayOfWeek:')) {
                    $propertyValue = DayOfWeek::from(\mb_substr($propertyValue, 10));
                } elseif (\str_starts_with($propertyValue, 'Date:')) {
                    $propertyValue = \DateTimeImmutable::createFromFormat(\DateTimeInterface::W3C, \mb_substr($propertyValue, 5));
                } elseif (\str_starts_with($propertyValue, 'URI:')) {
                    $propertyValue = new Uri(\mb_substr($propertyValue, 4));
                } else {
                    try {
                        $propertyValue = \json_decode($propertyValue, true, 512, JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        // then don't, just keep the value
                    }
                }
            }
        }

        return PropertyValuesToWrite::fromArray($properties);
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
