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

namespace Neos\ContentRepository\BehavioralTests\Tests\Parallel\ParallelRebaseWorkspaceAndPublishBaseWorkspace;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\BehavioralTests\Tests\Parallel\AbstractParallelTestCase;
use Neos\ContentRepository\BehavioralTests\TestSuite\DebugEventProjection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\WorkspaceCommandSkipped;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Fakes\FakeContentDimensionSourceFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeNodeTypeManagerFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeProjectionFactory;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\Event\EventTypes;
use Neos\EventStore\Model\EventStream\EventStreamFilter;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Assert;

/**
 * In Neos 9.0 it occurred that a rebase of a user workspace during publishing of the review base workspace lead to the rebase workspace still referencing the old review base content stream
 *
 * See https://github.com/neos/neos-development-collection/issues/5849
 */
class ParallelRebaseWorkspaceAndPublishBaseWorkspaceTest extends AbstractParallelTestCase
{
    private const SETUP_LOCK_PATH = __DIR__ . '/setup-lock';
    private const FIRST_IS_RUNNING_FLAG_PATH = __DIR__ . '/rebase-is-running-flag';

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
        $contentRepository->handle(
            CreateRootWorkspace::create(
                WorkspaceName::forLive(),
                ContentStreamId::fromString('cs-live')
            )
        );
        $contentRepository->handle(
            CreateRootNodeAggregateWithNode::create(
                WorkspaceName::forLive(),
                NodeAggregateId::fromString('lady-eleonode-rootford'),
                NodeTypeName::fromString(NodeTypeName::ROOT_NODE_TYPE_NAME)
            )
        );

        $contentRepository->handle(
            CreateNodeAggregateWithNode::create(
                WorkspaceName::forLive(),
                NodeAggregateId::fromString('nody-mc-nodeface'),
                NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
                $origin,
                NodeAggregateId::fromString('lady-eleonode-rootford'),
                initialPropertyValues: PropertyValuesToWrite::fromArray([
                    'title' => 'title-original'
                ])
            )
        );

        $contentRepository->handle(
            CreateWorkspace::create(
                WorkspaceName::fromString('review'),
                WorkspaceName::forLive(),
                ContentStreamId::fromString('cs-review-first')
            )
        );

        $contentRepository->handle(
            CreateWorkspace::create(
                WorkspaceName::fromString('user'),
                WorkspaceName::fromString('review'),
                ContentStreamId::fromString('cs-user-first')
            )
        );

        $this->contentRepository = $contentRepository;

        if (!flock($setupLockResource, LOCK_UN)) {
            throw new \RuntimeException('failed to release setup lock');
        }

        $this->log('setup finished');
    }

    /**
     * @test
     */
    public function whileBaseWorkspaceIsBeingPublished(): void
    {
        $this->log('publish started');

        touch(self::FIRST_IS_RUNNING_FLAG_PATH);

        try {
            for ($i = 0; $i <= 250; $i++) {
                $this->contentRepository->handle(
                    CreateNodeAggregateWithNode::create(
                        WorkspaceName::fromString('review'),
                        NodeAggregateId::fromString('nody-mc-nodeface-' . $i),
                        NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
                        OriginDimensionSpacePoint::createWithoutDimensions(),
                        NodeAggregateId::fromString('lady-eleonode-rootford'),
                        initialPropertyValues: PropertyValuesToWrite::fromArray([
                            'title' => 'title'
                        ])
                    )
                );
                $this->contentRepository->handle(
                    PublishWorkspace::create(
                        WorkspaceName::fromString('review')
                    )->withNewContentStreamId(ContentStreamId::fromString('cs-review-published-' . $i))
                );
            }
        } finally {
            unlink(self::FIRST_IS_RUNNING_FLAG_PATH);
        }

        $this->log('publish finished');
        Assert::assertTrue(true, 'No exception was thrown ;)');
    }

    /**
     * @test
     */
    public function thenConcurrentRebaseWorks(): void
    {
        if (!is_file(self::FIRST_IS_RUNNING_FLAG_PATH)) {
            $this->log('rebase waiting');

            $this->awaitFile(self::FIRST_IS_RUNNING_FLAG_PATH);
        }

        $this->log('rebase started');

        for ($i = 0; $i <= 1000; $i++) {
            try {
                try {
                    $this->contentRepository->handle(
                        RebaseWorkspace::create(
                            WorkspaceName::fromString('user')
                        )->withRebasedContentStreamId(ContentStreamId::fromString('cs-user-rebased-' . $i))
                    );
                    $this->log('Successfully rebased');
                    continue;
                } catch (WorkspaceCommandSkipped|ContentStreamDoesNotExist $expected) {
                    $this->log(
                        sprintf(
                            'Got expected %s: %s',
                            self::shortClassName($expected::class),
                            $expected->getMessage()
                        )
                    );
                    continue;
                }
            } catch (\Exception $thrownException) {
                $actualException = $thrownException;
                $this->log(
                    sprintf(
                        'Got exception %s: %s',
                        self::shortClassName($actualException::class),
                        $actualException->getMessage()
                    )
                );
                break;
            }
        }

        $reviewWorkspace = $this->contentRepository->getContentGraph(WorkspaceName::fromString('review'));
        $userWorkspace = $this->contentRepository->getContentGraph(WorkspaceName::fromString('user'));
        Assert::assertNotSame(
            'cs-user-first',
            $userWorkspace->getContentStreamId()->value,
        );
        $this->log('write finished' . $reviewWorkspace->getContentStreamId()->value . 'user: ' . $userWorkspace->getContentStreamId()->value);

        $this->assertEventsAreValid();

        // writing to user works!!!
        $this->contentRepository->handle(
            SetNodeProperties::create(
                WorkspaceName::fromString('user'),
                NodeAggregateId::fromString('nody-mc-nodeface-0'),
                OriginDimensionSpacePoint::createWithoutDimensions(),
                PropertyValuesToWrite::fromArray([
                    'title' => 'written-after-rebases'
                ])
            )
        );

        $node = $this->contentRepository->getContentGraph(WorkspaceName::fromString('user'))
            ->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty())
            ->findNodeById(NodeAggregateId::fromString('nody-mc-nodeface-0'));

        Assert::assertSame('written-after-rebases', $node?->getProperty('title'));
    }

    private function assertEventsAreValid(): void
    {
        $eventStore = $this->getEventStore($this->contentRepository->id);

        $contentStreamForksAndRemovals = $eventStore->load(VirtualStreamName::forCategory('ContentStream:'), EventStreamFilter::create(
            EventTypes::create(
                EventType::fromString('ContentStreamWasRemoved'),
                EventType::fromString('ContentStreamWasForked'),
            )
        ));

        $removedContentStreamsMap = [];
        foreach ($contentStreamForksAndRemovals as $eventEnvelope) {
            if ($eventEnvelope->event->type->value === 'ContentStreamWasRemoved') {
                if (array_key_exists($eventEnvelope->streamName->value, $removedContentStreamsMap)) {
                    Assert::fail(sprintf('ContentStream %s was removed twice: %s', $eventEnvelope->streamName->value, json_encode($eventEnvelope)));
                }
                $removedContentStreamsMap[$eventEnvelope->streamName->value] = true;
            } elseif ($eventEnvelope->event->type->value === 'ContentStreamWasForked') {
                $sourceContentStream = ContentStreamId::fromString(json_decode($eventEnvelope->event->data->value, true)['sourceContentStreamId']);
                if (array_key_exists(ContentStreamEventStreamName::fromContentStreamId($sourceContentStream)->value, $removedContentStreamsMap)) {
                    Assert::fail(sprintf('ContentStream %s was already removed but attempted to be used as fork source at %d for %s', $sourceContentStream->value, $eventEnvelope->sequenceNumber->value, $eventEnvelope->streamName->value));
                }
            }
        }

        Assert::assertNotEmpty($removedContentStreamsMap, 'No content streams were removed at all. Wrong query?');
    }
}
