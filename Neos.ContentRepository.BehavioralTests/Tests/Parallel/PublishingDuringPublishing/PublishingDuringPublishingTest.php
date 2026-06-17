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

namespace Neos\ContentRepository\BehavioralTests\Tests\Parallel\PublishingDuringPublishing;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\BehavioralTests\Tests\Parallel\AbstractParallelTestCase;
use Neos\ContentRepository\BehavioralTests\Tests\Parallel\WorkspacePublicationDuringWriting\WorkspacePublicationDuringWritingTest;
use Neos\ContentRepository\BehavioralTests\TestSuite\DebugEventProjection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Fakes\FakeContentDimensionSourceFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeNodeTypeManagerFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeProjectionFactory;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Assert;

/**
 * Test that no DeadlockException like: An exception occurred while executing a query: SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction
 * Is thrown during catchup as this would lead to projection failure which cannot be properly recovered from other than a replay.
 */
class PublishingDuringPublishingTest extends AbstractParallelTestCase
{
    private const SETUP_LOCK_PATH = __DIR__ . '/setup-lock';
    private const WRITING_IS_RUNNING_FLAG_PATH = __DIR__ . '/write-is-running-flag';

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
            'Neos.ContentRepository.Testing:Content' => [],
            'Neos.ContentRepository.Testing:Document' => [
                'properties' => [
                    'title' => [
                        'type' => 'string'
                    ]
                ],
                'childNodes' => [
                    'tethered-a' => [
                        'type' => 'Neos.ContentRepository.Testing:Content'
                    ],
                    'tethered-b' => [
                        'type' => 'Neos.ContentRepository.Testing:Content'
                    ],
                    'tethered-c' => [
                        'type' => 'Neos.ContentRepository.Testing:Content'
                    ],
                    'tethered-d' => [
                        'type' => 'Neos.ContentRepository.Testing:Content'
                    ],
                    'tethered-e' => [
                        'type' => 'Neos.ContentRepository.Testing:Content'
                    ]
                ]
            ]
        ]);

        /** Hack until https://github.com/neos/neos-development-collection/issues/5869 is fixed */
        \Neos\ContentRepository\Dbal\MysqlPlatformContentRepositoryLocker::enableForContentRepository(ContentRepositoryId::fromString('test_parallel'));

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
            WorkspaceName::fromString('user-test-one'),
            WorkspaceName::forLive(),
            ContentStreamId::fromString('user-test-cs-one')
        ));
        $contentRepository->handle(CreateWorkspace::create(
            WorkspaceName::fromString('user-test-two'),
            WorkspaceName::forLive(),
            ContentStreamId::fromString('user-test-cs-two')
        ));

        $this->contentRepository = $contentRepository;

        if (!flock($setupLockResource, LOCK_UN)) {
            throw new \RuntimeException('failed to release setup lock');
        }

        $this->log('setup finished');
    }

    /**
     * @test
     */
    public function whileANodesArWrittenOnLive(): void
    {
        $this->log('1. writing & publishing started');

        touch(self::WRITING_IS_RUNNING_FLAG_PATH);

        try {
            for ($i = 0; $i <= 100; $i++) {
                if (!$this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('user-test-one'))->hasPublishableChanges()) {
                    $this->contentRepository->handle(CreateNodeAggregateWithNode::create(
                        WorkspaceName::fromString('user-test-one'),
                        NodeAggregateId::fromString('nody-mc-nodeface-' . $i),
                        NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
                        OriginDimensionSpacePoint::createWithoutDimensions(),
                        NodeAggregateId::fromString('lady-eleonode-rootford'),
                        initialPropertyValues: PropertyValuesToWrite::fromArray([
                            'title' => 'title'
                        ])
                    ));
                }

                try {
                    $this->contentRepository->handle(PublishWorkspace::create(
                        WorkspaceName::fromString('user-test-one')
                    )->withNewContentStreamId(
                        ContentStreamId::fromString('user-test-cs-one-' . $i)
                    ));
                } catch (ConcurrencyException $concurrencyException) {
                    /** Two simultaneous publish operations can get a concurrency exception as anticipated {@see WorkspacePublicationDuringWritingTest} */
                    $this->log(sprintf('Got likely expected exception %s: %s', self::shortClassName($concurrencyException::class), $concurrencyException->getMessage()));
                }
            }
        } finally {
            unlink(self::WRITING_IS_RUNNING_FLAG_PATH);
        }

        $this->log('1. writing & publishing finished');
        Assert::assertTrue(true, 'No exception was thrown ;)');

        $subgraph = $this->contentRepository->getContentGraph(WorkspaceName::forLive())->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty());
        $childNodes = $subgraph->findChildNodes(NodeAggregateId::fromString('lady-eleonode-rootford'), FindChildNodesFilter::create());
        $childNodes = $childNodes->filter(fn (Node $node) => str_starts_with($node->aggregateId->value, 'nody-mc-nodeface-'));
        Assert::assertGreaterThan(20, $childNodes->count(), 'To few nodes actually published and created');
    }

    /**
     * @test
     */
    public function thenConcurrentPublishAreNotDeadlocked(): void
    {
        if (!is_file(self::WRITING_IS_RUNNING_FLAG_PATH)) {
            $this->log('waiting for 2. writing');

            $this->awaitFile(self::WRITING_IS_RUNNING_FLAG_PATH);
            // If write is the process that does the (slowish) setup, and then waits for the rebase to start,
            // We give the CR some time to close the content stream
            // TODO find another way than to randomly wait!!!
            // The problem is, if we dont sleep it happens often that the modification works only then the rebase is startet _really_
            // Doing the modification several times in hope that the second one fails will likely just stop the rebase thread as it cannot close
            usleep(10000);
        }

        $this->log('2. writing & publishing started');

        for ($i = 0; $i <= 100; $i++) {
            if (!$this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('user-test-two'))->hasPublishableChanges()) {
                $this->contentRepository->handle(CreateNodeAggregateWithNode::create(
                    WorkspaceName::fromString('user-test-two'),
                    NodeAggregateId::fromString('sir-david-nodenborough-' . $i),
                    NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
                    OriginDimensionSpacePoint::createWithoutDimensions(),
                    NodeAggregateId::fromString('lady-eleonode-rootford'),
                    initialPropertyValues: PropertyValuesToWrite::fromArray([
                        'title' => 'title'
                    ])
                ));
            }

            try {
                $this->contentRepository->handle(PublishWorkspace::create(
                    WorkspaceName::fromString('user-test-two')
                )->withNewContentStreamId(
                    ContentStreamId::fromString('user-test-cs-two-' . $i)
                ));
            } catch (ConcurrencyException $concurrencyException) {
                /** Two simultaneous publish operations can get a concurrency exception as anticipated {@see WorkspacePublicationDuringWritingTest} */
                $this->log(sprintf('Got likely expected exception %s: %s', self::shortClassName($concurrencyException::class), $concurrencyException->getMessage()));
            }
        }

        $this->log('2. writing & publishing finished');

        Assert::assertTrue(true, 'No exception was thrown ;)');

        $subgraph = $this->contentRepository->getContentGraph(WorkspaceName::forLive())->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty());
        $childNodes = $subgraph->findChildNodes(NodeAggregateId::fromString('lady-eleonode-rootford'), FindChildNodesFilter::create());
        $childNodes = $childNodes->filter(fn (Node $node) => str_starts_with($node->aggregateId->value, 'sir-david-nodenborough-'));
        Assert::assertGreaterThan(20, $childNodes->count(), 'To few nodes actually published and created');
    }
}
