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
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
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
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsClosed;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
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
            $this->awaitFileRemoval(self::SETUP_IS_RUNNING_FLAG_PATH, 60000); // 60s for CR setup
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
            ContentStreamId::fromString('live-cs-id')
        ));
        $contentRepository->handle(CreateRootNodeAggregateWithNode::create(
            WorkspaceName::forLive(),
            NodeAggregateId::fromString('lady-eleonode-rootford'),
            NodeTypeName::fromString(NodeTypeName::ROOT_NODE_TYPE_NAME)
        ));
        $contentRepository->handle(CreateNodeAggregateWithNode::create(
            WorkspaceName::forLive(),
            NodeAggregateId::fromString('nody-mc-nodeface'),
            NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
            $origin,
            NodeAggregateId::fromString('lady-eleonode-rootford'),
            initialPropertyValues: PropertyValuesToWrite::fromArray([
                'title' => 'title'
            ])
        ));
        $contentRepository->handle(CreateWorkspace::create(
            WorkspaceName::fromString('user-test'),
            WorkspaceName::forLive(),
            new WorkspaceTitle('User'),
            new WorkspaceDescription('The user workspace'),
            ContentStreamId::fromString('user-cs-id')
        ));
        for ($i = 0; $i <= 1000; $i++) {
            $contentRepository->handle(CreateNodeAggregateWithNode::create(
                WorkspaceName::forLive(),
                NodeAggregateId::fromString('nody-mc-nodeface-' . $i),
                NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
                $origin,
                NodeAggregateId::fromString('lady-eleonode-rootford'),
                initialPropertyValues: PropertyValuesToWrite::fromArray([
                    'title' => 'title'
                ])
            ));
            // give the database lock some time to recover
            usleep(5000);
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
            )->withRebasedContentStreamId(ContentStreamId::fromString('user-test-rebased')));
        } catch (\RuntimeException $runtimeException) {
            $exception = $runtimeException;
        }
        unlink(self::REBASE_IS_RUNNING_FLAG_PATH);
        Assert::assertNull($exception);
    }

    /**
     * @test
     * @group parallel
     */
    public function thenConcurrentCommandsLeadToAnException(): void
    {
        $this->awaitFile(self::REBASE_IS_RUNNING_FLAG_PATH);
        // give the CR some time to close the content stream
        usleep(10000);
        $origin = OriginDimensionSpacePoint::createWithoutDimensions();
        $exceptionIsThrownAsExpected = false;
        $actualException = 'none';
        try {
            $this->contentRepository->handle(SetNodeProperties::create(
                WorkspaceName::fromString('user-test'),
                NodeAggregateId::fromString('nody-mc-nodeface'),
                $origin,
                PropertyValuesToWrite::fromArray([
                    'title' => 'title47b'
                ])
            ));
        } catch (\Exception $thrownException) {
            $exceptionIsThrownAsExpected
                = $thrownException instanceof ContentStreamIsClosed || $thrownException instanceof ConcurrencyException;
            $actualException = get_class($thrownException);
        }

        unlink(self::SETUP_IS_DONE_FLAG_PATH);
        Assert::assertTrue($exceptionIsThrownAsExpected, 'Expected exception of type ' . ContentStreamIsClosed::class
            . ' or ' . ConcurrencyException::class . ', ' . $actualException . ' thrown'
        );
    }

    private function awaitFile(string $filename): void
    {
        $waiting = 0;
        while (!is_file($filename)) {
            usleep(1000);
            $waiting++;
            clearstatcache(true, $filename);
            if ($waiting > 60000) {
                throw new \Exception('timeout while waiting on file ' . $filename);
            }
        }
    }

    private function awaitFileRemoval(string $filename, int $maximumCycles = 2000): void
    {
        $waiting = 0;
        while (is_file($filename)) {
            usleep(10000);
            $waiting++;
            clearstatcache(true, $filename);
            if ($waiting > $maximumCycles) {
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
