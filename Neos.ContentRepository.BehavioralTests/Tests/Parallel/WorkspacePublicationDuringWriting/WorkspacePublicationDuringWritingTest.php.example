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

namespace Neos\ContentRepository\BehavioralTests\Tests\Parallel\WorkspacePublicationDuringWriting;

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
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
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

class WorkspacePublicationDuringWritingTest extends AbstractParallelTestCase
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
                WorkspaceName::fromString('user-test'),
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
    public function whileANodesArWrittenOnLive(): void
    {
        $this->log('writing started');

        touch(self::WRITING_IS_RUNNING_FLAG_PATH);

        try {
            for ($i = 0; $i <= 50; $i++) {
                $this->contentRepository->handle(
                    SetNodeProperties::create(
                        WorkspaceName::forLive(),
                        NodeAggregateId::fromString('nody-mc-nodeface'),
                        OriginDimensionSpacePoint::createWithoutDimensions(),
                        PropertyValuesToWrite::fromArray([
                            'title' => 'changed-title-' . $i
                        ])
                    )
                );
            }
        } finally {
            unlink(self::WRITING_IS_RUNNING_FLAG_PATH);
        }

        $this->log('writing finished');
        Assert::assertTrue(true, 'No exception was thrown ;)');

        $subgraph = $this->contentRepository->getContentGraph(WorkspaceName::forLive())->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty());
        $node = $subgraph->findNodeById(NodeAggregateId::fromString('nody-mc-nodeface'));
        Assert::assertNotNull($node);
        Assert::assertSame($node->getProperty('title'), 'changed-title-50');
    }

    /**
     * @test
     * @group parallel
     */
    public function thenConcurrentPublishLeadsToException(): void
    {
        if (!is_file(self::WRITING_IS_RUNNING_FLAG_PATH)) {
            $this->log('waiting to publish');

            $this->awaitFile(self::WRITING_IS_RUNNING_FLAG_PATH);
            // If write is the process that does the (slowish) setup, and then waits for the rebase to start,
            // We give the CR some time to close the content stream
            // TODO find another way than to randomly wait!!!
            // The problem is, if we dont sleep it happens often that the modification works only then the rebase is startet _really_
            // Doing the modification several times in hope that the second one fails will likely just stop the rebase thread as it cannot close
            usleep(10000);
        }

        $this->log('publish started');


        /*
        // NOTE, can also be tested with PartialPublish, or PartialPublish leading to a full publish, but this test only allows one at time :)

        $nodesForAFullPublish = 5000;
        $nodesForAPartialPublish = $nodesForAFullPublish - 1;

        $nodeIdToPublish = [];
        for ($i = 0; $i <= $nodesForAPartialPublish; $i++) {
            $nodeIdToPublish[] = new NodeIdToPublishOrDiscard(
                NodeAggregateId::fromString('nody-mc-nodeface-' . $i), // see nodes created above
                DimensionSpacePoint::createWithoutDimensions()
            );
        }

        $this->contentRepository->handle(PublishIndividualNodesFromWorkspace::create(
            WorkspaceName::fromString('user-test'),
            NodeIdsToPublishOrDiscard::create(...$nodeIdToPublish)
        ));
        */

        $actualException = null;
        try {
            $this->contentRepository->handle(PublishWorkspace::create(
                WorkspaceName::fromString('user-test')
            ));
        } catch (\Exception $thrownException) {
            $actualException = $thrownException;
            $this->log(sprintf('Got exception %s: %s', self::shortClassName($actualException::class), $actualException->getMessage()));
        }

        $this->log('publish finished');

        if ($actualException === null) {
            Assert::fail(sprintf('No exception was thrown'));
        }

        Assert::assertInstanceOf(ConcurrencyException::class, $actualException);

        $this->awaitFileRemoval(self::WRITING_IS_RUNNING_FLAG_PATH);

        // writing to user works!!!
        try {
            $this->contentRepository->handle(
                SetNodeProperties::create(
                    WorkspaceName::fromString('user-test'),
                    NodeAggregateId::fromString('nody-mc-nodeface'),
                    OriginDimensionSpacePoint::createWithoutDimensions(),
                    PropertyValuesToWrite::fromArray([
                        'title' => 'written-after-failed-publish'
                    ])
                )
            );
        } catch (ContentStreamIsClosed $exception) {
            Assert::fail(sprintf('Workspace that failed to be publish cannot be written: %s', $exception->getMessage()));
        }

        $node = $this->contentRepository->getContentGraph(WorkspaceName::fromString('user-test'))
            ->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty())
            ->findNodeById(NodeAggregateId::fromString('nody-mc-nodeface'));

        Assert::assertSame('written-after-failed-publish', $node?->getProperty('title'));
    }
}
