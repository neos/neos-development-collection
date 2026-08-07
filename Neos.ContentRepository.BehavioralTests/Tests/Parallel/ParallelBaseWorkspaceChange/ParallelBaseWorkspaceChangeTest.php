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

namespace Neos\ContentRepository\BehavioralTests\Tests\Parallel\ParallelBaseWorkspaceChange;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\BehavioralTests\Tests\Parallel\AbstractParallelTestCase;
use Neos\ContentRepository\BehavioralTests\TestSuite\DebugEventProjection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\WorkspaceCommandSkipped;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Fakes\FakeContentDimensionSourceFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeNodeTypeManagerFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeProjectionFactory;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\Event\EventTypes;
use Neos\EventStore\Model\EventStream\EventStreamFilter;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Assert;

/**
 * In Neos 9.0 it occurred that a base workspace change was done without locking and thus the previous content stream was removed twice which is illegal:
 *
 * https://github.com/neos/neos-development-collection/issues/5877
 */
class ParallelBaseWorkspaceChangeTest extends AbstractParallelTestCase
{
    private const SETUP_LOCK_PATH = __DIR__ . '/setup-lock';
    private const WRITING_IS_RUNNING_FLAG_PATH = __DIR__ . '/write-is-running-flag';

    private const VARIETY_SIZE = 5;

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

        for ($i = 0; $i <= self::VARIETY_SIZE ; $i++) {
            $contentRepository->handle(CreateWorkspace::create(
                WorkspaceName::fromString('review-' . $i),
                WorkspaceName::forLive(),
                ContentStreamId::fromString('cs-review-' . $i)
            ));
        }

        $contentRepository->handle(CreateWorkspace::create(
            WorkspaceName::fromString('user'),
            WorkspaceName::forLive(),
            ContentStreamId::fromString('user-cs-initial')
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
    public function whileANodesArePublishedToLive(): void
    {
        $this->log('1. change base started');

        touch(self::WRITING_IS_RUNNING_FLAG_PATH);

        $successFullChanged = 0;
        try {
            for ($i = 0; $i <= 30; $i++) {
                $randomTarget = WorkspaceName::fromString('review-' . random_int(0, self::VARIETY_SIZE));
                try {
                    $this->contentRepository->handle(ChangeBaseWorkspace::create(
                        WorkspaceName::fromString('user'),
                        $randomTarget,
                    )->withNewContentStreamId(
                        ContentStreamId::fromString(sprintf('%d-%d', getmypid(), $i))
                    ));
                    $successFullChanged++;
                } catch (ConcurrencyException|WorkspaceCommandSkipped|ContentStreamDoesNotExistYet $concurrencyException) {
                    $this->log(sprintf('Got likely expected exception %s: %s', self::shortClassName($concurrencyException::class), $concurrencyException->getMessage()));
                }
            }
        } finally {
            unlink(self::WRITING_IS_RUNNING_FLAG_PATH);
        }

        $this->log('1. base workspace change finished with: ' . $successFullChanged);
        Assert::assertGreaterThan(1, $successFullChanged, 'Base workspace was not changed');

        $this->assertEventsAreValid();
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

        $this->log('2. base workspace change started');

        $successFullChanged = 0;
        for ($i = 0; $i <= 30; $i++) {
            $randomTarget = WorkspaceName::fromString('review-' . random_int(0, self::VARIETY_SIZE));
            try {
                $this->contentRepository->handle(ChangeBaseWorkspace::create(
                    WorkspaceName::fromString('user'),
                    $randomTarget,
                )->withNewContentStreamId(
                    ContentStreamId::fromString(sprintf('%d-%d', getmypid(), $i))
                ));
                $successFullChanged++;
            } catch (ConcurrencyException|WorkspaceCommandSkipped|ContentStreamDoesNotExistYet $concurrencyException) {
                $this->log(sprintf('Got likely expected exception %s: %s', self::shortClassName($concurrencyException::class), $concurrencyException->getMessage()));
            }
        }

        $this->log('2. base workspace change finished with: ' . $successFullChanged);
        Assert::assertGreaterThan(1, $successFullChanged, 'Base workspace was not changed');

        $this->assertEventsAreValid();
    }

    private function assertEventsAreValid(): void
    {
        $eventStore = $this->getEventStore($this->contentRepository->id);

        $contentStreamWasRemovedEvents = $eventStore->load(VirtualStreamName::forCategory('ContentStream:'), EventStreamFilter::create(
            EventTypes::create(
                EventType::fromString('ContentStreamWasRemoved')
            )
        ));

        $removedContentStreamsMap = [];
        foreach ($contentStreamWasRemovedEvents as $eventEnvelope) {
            if (array_key_exists($eventEnvelope->streamName->value, $removedContentStreamsMap)) {
                Assert::fail(sprintf('ContentStream %s was removed twice: %s', $eventEnvelope->streamName->value, json_encode($eventEnvelope)));
            }
            $removedContentStreamsMap[$eventEnvelope->streamName->value] = true;
        }

        Assert::assertNotEmpty($removedContentStreamsMap, 'No content streams were removed at all. Wrong query?');
    }
}
