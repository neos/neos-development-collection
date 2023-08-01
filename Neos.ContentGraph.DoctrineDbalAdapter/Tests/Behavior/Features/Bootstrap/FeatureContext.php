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

require_once(__DIR__ . '/../../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/ContentStreamForking.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeCopying.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeCreation.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeDisabling.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeModification.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeMove.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeReferencing.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeRemoval.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeRenaming.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeTypeChange.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/NodeVariation.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/WorkspaceCreation.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/WorkspaceDiscarding.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/Features/WorkspacePublishing.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentSubgraphTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentUserTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentDateTimeTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/GenericCommandExecutionAndEventPublication.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/NodeTraversalTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/ProjectedNodeAggregateTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/ProjectedNodeTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/EventSourcedTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/MigrationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Security/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/StructureAdjustmentsTrait.php');
require_once(__DIR__ . '/ProjectionIntegrityViolationDetectionTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/EventSourcedTrait.php');

use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap\ProjectionIntegrityViolationDetectionTrait;
use Neos\ContentRepository\BehavioralTests\Tests\Functional\BehatTestHelper;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\ContentRepositoryInternalsFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeClockFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeUserIdProviderFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Utility\Environment;

/**
 * Features context
 */
class FeatureContext implements \Behat\Behat\Context\Context
{
    use FlowContextTrait;
    use IsolatedBehatStepsTrait;
    use ProjectionIntegrityViolationDetectionTrait;
    use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\CRTestSuiteTrait;
    use NodeOperationsTrait;

    protected string $behatTestHelperObjectName = BehatTestHelper::class;

    protected ?ContentRepositoryRegistry $contentRepositoryRegistry = null;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();
        $this->setupEventSourcedTrait();
        $this->setupDbalGraphAdapterIntegrityViolationTrait();
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
        $this->contentRepositoryInternals = $this->contentRepositoryRegistry->buildService(
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

        return $contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            $factory
        );
    }

    protected function getDbalClient(): DbalClientInterface
    {
        return $this->dbalClient;
    }
}
