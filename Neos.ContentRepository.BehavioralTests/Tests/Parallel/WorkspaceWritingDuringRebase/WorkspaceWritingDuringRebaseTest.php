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

namespace Neos\ContentRepository\BehavioralTests\Tests\Parallel\WorkspaceWritingDuringRebase;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\BehavioralTests\Tests\Parallel\AbstractParallelTestCase;
use Neos\ContentRepository\BehavioralTests\TestSuite\DebugEventProjection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsClosed;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Fakes\FakeContentDimensionSourceFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeNodeTypeManagerFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeProjectionFactory;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Assert;

class WorkspaceWritingDuringRebaseTest extends AbstractParallelTestCase

{
    private const SETUP_LOCK_PATH = __DIR__ . '/setup-lock';
    private const REBASE_IS_RUNNING_FLAG_PATH = __DIR__ . '/rebase-is-running-flag';

    private ContentRepository $contentRepository;

    protected ObjectManagerInterface $objectManager;

    public function setUp(): void
    {
        parent::setUp();
        $this->log('------ process started ------');

        $debugProjection = new DebugEventProjection(
            'cr_test_parallel_debug_projection',
            $this->objectManager->get(Connection::class)
        );
        FakeProjectionFactory::setProjection(
            'debug',
            $debugProjection
        );

        FakeContentDimensionSourceFactory::setWithoutDimensions();
        FakeNodeTypeManagerFactory::setConfiguration([
            'Neos.ContentRepository:Root' => [],
            'Neos.ContentRepository.Testing:Document' => [
                'properties' => [
                    'title' => [
                        'type' => 'string'
                    ]
                ]
            ]
        ]);

        $setupLockResource = fopen(self::SETUP_LOCK_PATH, 'w+');

        $exclusiveNonBlockingLockResult = flock($setupLockResource, LOCK_EX | LOCK_NB);
        if ($exclusiveNonBlockingLockResult === false) {
            $this->log('waiting for setup');
            if (!flock($setupLockResource, LOCK_SH)) {
                throw new \RuntimeException('failed to acquire blocking shared lock');
            }
            $this->contentRepository = $this->contentRepositoryRegistry
                ->get(ContentRepositoryId::fromString('test_parallel'));
            $this->log('wait for setup finished');
            return;
        }

        $this->log('setup started');
        $contentRepository = $this->setUpContentRepository(ContentRepositoryId::fromString('test_parallel'));

        $origin = OriginDimensionSpacePoint::createWithoutDimensions();
        $contentRepository->handle(CreateRootWorkspace::create(
            WorkspaceName::forLive(),
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
                'title' => 'title-original'
            ])
        ));
        $contentRepository->handle(CreateWorkspace::create(
            WorkspaceName::fromString('user-test'),
            WorkspaceName::forLive(),
            ContentStreamId::fromString('user-cs-id')
        ));
        for ($i = 0; $i <= 5000; $i++) {
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
        }
        $this->contentRepository = $contentRepository;

        if (!flock($setupLockResource, LOCK_UN)) {
            throw new \RuntimeException('failed to release setup lock');
        }

        $this->log('setup finished');
    }

    /**
     * @test
     * @group parallel
     */
    public function whileAWorkspaceIsBeingRebased(): void
    {
        $workspaceName = WorkspaceName::fromString('user-test');
        $this->log('rebase started');

        touch(self::REBASE_IS_RUNNING_FLAG_PATH);

        try {
            $this->contentRepository->handle(
                RebaseWorkspace::create($workspaceName)
                    ->withRebasedContentStreamId(ContentStreamId::fromString('user-cs-rebased'))
                    ->withErrorHandlingStrategy(RebaseErrorHandlingStrategy::STRATEGY_FORCE));
        } finally {
            unlink(self::REBASE_IS_RUNNING_FLAG_PATH);
        }

        $this->log('rebase finished');
        Assert::assertTrue(true, 'No exception was thrown ;)');
    }

    /**
     * @test
     * @group parallel
     */
    public function thenConcurrentCommandsLeadToAnException(): void
    {
        if (!is_file(self::REBASE_IS_RUNNING_FLAG_PATH)) {
            $this->log('write waiting');

            $this->awaitFile(self::REBASE_IS_RUNNING_FLAG_PATH);
            // If write is the process that does the (slowish) setup, and then waits for the rebase to start,
            // We give the CR some time to close the content stream
            // TODO find another way than to randomly wait!!!
            // The problem is, if we dont sleep it happens often that the modification works only then the rebase is startet _really_
            // Doing the modification several times in hope that the second one fails will likely just stop the rebase thread as it cannot close
            usleep(10000);
        }

        $this->log('write started');

        $workspaceDuringRebase = $this->contentRepository->getContentGraph(WorkspaceName::fromString('user-test'));
        Assert::assertSame('user-cs-id', $workspaceDuringRebase->getContentStreamId()->value,
            'The parallel tests expects the workspace to still point to the original cs.'
        );

        $origin = OriginDimensionSpacePoint::createWithoutDimensions();
        $actualException = null;
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
            $actualException = $thrownException;
            $this->log(sprintf('Got exception %s: %s', self::shortClassName($actualException::class), $actualException->getMessage()));
        }

        $this->log('write finished');

        $node = $this->contentRepository->getContentGraph(WorkspaceName::fromString('user-test'))
            ->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty())
            ->findNodeById(NodeAggregateId::fromString('nody-mc-nodeface'));

        if ($actualException === null) {
            Assert::fail(sprintf('No exception was thrown. Mutated Node: %s', json_encode($node?->properties->serialized())));
        }

        Assert::assertThat($actualException, self::logicalOr(
            self::isInstanceOf(ContentStreamIsClosed::class),
            self::isInstanceOf(ConcurrencyException::class), // todo is only thrown theoretical? but not during tests here ...
        ));

        Assert::assertSame('title-original', $node?->getProperty('title'));
    }
}
