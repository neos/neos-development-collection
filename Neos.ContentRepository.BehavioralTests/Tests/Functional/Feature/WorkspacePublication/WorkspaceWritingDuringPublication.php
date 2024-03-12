<?php

/*
 * This file is part of the Neos.ContentRepository.BehavioralTests package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Feature\WorkspacePublication;

use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinPyStringNodeBasedNodeTypeManagerFactory;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinTableNodeBasedContentDimensionSourceFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimension;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\NodeType\DefaultNodeLabelGeneratorFactory;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\CatchUpTriggerWithSynchronousOption;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Assert;

/**
 * Parallel test cases for workspace publication
 */
class WorkspaceWritingDuringPublication extends FunctionalTestCase
{
    use CRTestSuiteTrait;
    use CRBehavioralTestsSubjectProvider;

    private const REBASE_IS_RUNNING_FLAG_PATH = __DIR__ . '/rebase-is-running-flag';
    private const SETUP_IS_RUNNING_FLAG_PATH = __DIR__ . '/setup-is-running-flag';
    private const SETUP_IS_DONE_FLAG_PATH = __DIR__ . '/setup-is-done-flag';

    private ?ContentRepository $contentRepository = null;

    private ?ContentRepositoryRegistry $contentRepositoryRegistry = null;

    public function setUp(): void
    {
        parent::setUp();
        CatchUpTriggerWithSynchronousOption::enableSynchronicityForSpeedingUpTesting();
        GherkinTableNodeBasedContentDimensionSourceFactory::$contentDimensionsToUse = new class implements ContentDimensionSourceInterface
        {
            public function getDimension(ContentDimensionId $dimensionId): ?ContentDimension
            {
                return null;
            }
            public function getContentDimensionsOrderedByPriority(): array
            {
                return [];
            }
        };

        GherkinPyStringNodeBasedNodeTypeManagerFactory::$nodeTypesToUse = new NodeTypeManager(
            fn (): array => [
                'Neos.ContentRepository:Root' => [],
                'Neos.ContentRepository.Testing:Document' => [
                    'properties' => [
                        'title' => [
                            'type' => 'string'
                        ]
                    ]
                ]
            ],
            new DefaultNodeLabelGeneratorFactory()
        );
        $this->contentRepositoryRegistry = $this->objectManager->get(ContentRepositoryRegistry::class);

        if (is_file(self::SETUP_IS_RUNNING_FLAG_PATH)) {
            $this->awaitFileRemoval(self::SETUP_IS_RUNNING_FLAG_PATH);
        }
        if (is_file(self::SETUP_IS_DONE_FLAG_PATH)) {
            $this->contentRepository = $this->contentRepositoryRegistry
                ->get(ContentRepositoryId::fromString('default'));
            return;
        }
        touch(self::SETUP_IS_RUNNING_FLAG_PATH);

        $contentRepository = $this->setUpContentRepository(ContentRepositoryId::fromString('default'));

        $origin = OriginDimensionSpacePoint::createWithoutDimensions();
        $contentRepository->handle(CreateRootWorkspace::create(
            WorkspaceName::forLive(),
            new WorkspaceTitle('Live'),
            new WorkspaceDescription('The live workspace'),
            ContentStreamId::create()
        ))->block();
        $contentRepository->handle(CreateRootNodeAggregateWithNode::create(
            WorkspaceName::forLive(),
            NodeAggregateId::fromString('lady-eleonode-rootford'),
            NodeTypeName::fromString(NodeTypeName::ROOT_NODE_TYPE_NAME)
        ))->block();
        $contentRepository->handle(CreateNodeAggregateWithNode::create(
            WorkspaceName::forLive(),
            NodeAggregateId::fromString('nody-mc-nodeface'),
            NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
            $origin,
            NodeAggregateId::fromString('lady-eleonode-rootford'),
            initialPropertyValues: PropertyValuesToWrite::fromArray([
                'title' => 'title'
            ])
        ))->block();
        $contentRepository->handle(CreateWorkspace::create(
            WorkspaceName::fromString('user-test'),
            WorkspaceName::forLive(),
            new WorkspaceTitle('User'),
            new WorkspaceDescription('The user workspace'),
            ContentStreamId::create()
        ))->block();
        for ($i = 0; $i <= 500; $i++) {
            $contentRepository->handle(CreateNodeAggregateWithNode::create(
                WorkspaceName::forLive(),
                NodeAggregateId::fromString('nody-mc-nodeface-' . $i),
                NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
                $origin,
                NodeAggregateId::fromString('lady-eleonode-rootford'),
                initialPropertyValues: PropertyValuesToWrite::fromArray([
                    'title' => 'title'
                ])
            ))->block();
        }
        $this->contentRepository = $contentRepository;

        touch(self::SETUP_IS_DONE_FLAG_PATH);
        unlink(self::SETUP_IS_RUNNING_FLAG_PATH);
    }

    /**
     * @test
     * @group parallel
     */
    public function whileAWorkspaceIsBeingRebased(): void
    {
        touch(self::REBASE_IS_RUNNING_FLAG_PATH);
        $workspaceName = WorkspaceName::fromString('user-test');
        $exception = null;
        try {
            $this->contentRepository->handle(RebaseWorkspace::create(
                $workspaceName,
            ))->block();
        } catch (\RuntimeException $runtimeException) {
            $exception = $runtimeException;
        }
        Assert::assertNull($exception);
        unlink(self::REBASE_IS_RUNNING_FLAG_PATH);
    }

    /**
     * @test
     * @group parallel
     */
    public function thenConcurrentCommandsAreStillAppliedToIt(): void
    {
        $this->awaitFile(self::REBASE_IS_RUNNING_FLAG_PATH);
        $origin = OriginDimensionSpacePoint::createWithoutDimensions();
        $exception = null;
        try {
            $this->contentRepository->handle(SetNodeProperties::create(
                WorkspaceName::fromString('user-test'),
                NodeAggregateId::fromString('nody-mc-nodeface'),
                $origin,
                PropertyValuesToWrite::fromArray([
                    'title' => 'title47b'
                ])
            ))->block();
        } catch (\Exception $concurrencyException) {
            $exception = $concurrencyException;
        }
        if ($exception instanceof \Exception) {
            Assert::assertInstanceOf(ConcurrencyException::class, $exception);
        } else {
            $this->awaitFileRemoval(self::REBASE_IS_RUNNING_FLAG_PATH);
            $node = $this->contentRepository->getContentGraph()->getSubgraph(
                $this->contentRepository->getWorkspaceFinder()
                    ->findOneByName(WorkspaceName::fromString('user-test'))
                    ->currentContentStreamId,
                DimensionSpacePoint::createWithoutDimensions(),
                VisibilityConstraints::withoutRestrictions()
            )->findNodeById(NodeAggregateId::fromString('nody-mc-nodeface'));
            Assert::assertSame('title47b', $node->getProperty('title'));
        }

        unlink(self::SETUP_IS_DONE_FLAG_PATH);
    }

    private function awaitFile(string $filename): void
    {
        $waiting = 0;
        while (!is_file($filename)) {
            usleep(1000);
            $waiting++;
            clearstatcache(true, $filename);
            if ($waiting > 10000) {
                throw new \Exception('timeout while waiting on file ' . $filename);
            }
        }
    }

    private function awaitFileRemoval(string $filename): void
    {
        $waiting = 0;
        while (is_file($filename)) {
            usleep(10000);
            $waiting++;
            clearstatcache(true, $filename);
            if ($waiting > 1000) {
                throw new \Exception('timeout while waiting on removal of file ' . $filename);
            }
        }
    }

    protected function getObject(string $className): object
    {
        return $this->objectManager->get($className);
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
