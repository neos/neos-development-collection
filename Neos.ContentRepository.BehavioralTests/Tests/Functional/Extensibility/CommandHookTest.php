<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Extensibility;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandler\Commands;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\PublishedEvents;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

class CommandHookTest extends AbstractExtensibilityTestCase
{
    public function testCommandHookReceivesCommandAndEvents(): void
    {
        $command = CreateRootWorkspace::create(WorkspaceName::forLive(), ContentStreamId::fromString('cs-live'));
        $expectedEvents = PublishedEvents::fromArray([
            new ContentStreamWasCreated(ContentStreamId::fromString('cs-live')),
            new RootWorkspaceWasCreated(WorkspaceName::forLive(), ContentStreamId::fromString('cs-live'))
        ]);

        $this->fakeCommandHook->expects(self::once())->method('onBeforeHandle')->with($command)->willReturn($command);
        $this->fakeCommandHook->expects(self::once())->method('onAfterHandle')->with($command, $expectedEvents)->willReturn(Commands::createEmpty());

        $this->contentRepository->handle($command);
    }

    /**
     * onBeforeHandle and onAfterHandle are invoked in various cases, for control flow aware commands and also simple ones
     */
    public function testCommandHookWithMultipleCommands(): void
    {
        $testCases = [
            [
                'command' => CreateRootWorkspace::create(WorkspaceName::forLive(), ContentStreamId::fromString('cs-live')),
                'eventClassNames' => [ContentStreamWasCreated::class, RootWorkspaceWasCreated::class]
            ],
            [
                'command' => CreateWorkspace::create(WorkspaceName::fromString('user'), WorkspaceName::forLive(), ContentStreamId::fromString('cs-user')),
                'eventClassNames' => [ContentStreamWasForked::class, WorkspaceWasCreated::class]
            ],
            [
                'command' => CreateRootNodeAggregateWithNode::create(WorkspaceName::fromString('user'), NodeAggregateId::fromString('node'), NodeTypeName::fromString(NodeTypeName::ROOT_NODE_TYPE_NAME)),
                'eventClassNames' => [RootNodeAggregateWithNodeWasCreated::class]
            ],
            [
                'command' => PublishWorkspace::create(WorkspaceName::fromString('user')),
                'eventClassNames' => [ContentStreamWasClosed::class, RootNodeAggregateWithNodeWasCreated::class, ContentStreamWasForked::class, WorkspaceWasPublished::class, ContentStreamWasRemoved::class]
            ],
        ];

        $this->fakeCommandHook->expects($i = self::exactly(count($testCases)))->method('onBeforeHandle')->willReturnCallback(function (CommandInterface $command) use ($i, $testCases) {
            $caseIndex = $i->getInvocationCount() - 1;

            $testCase = $testCases[$caseIndex];
            self::assertEquals($testCase['command'], $command, sprintf('The command at step %s doesnt match as expected', $caseIndex));
            return $testCase['command'];
        });
        $this->fakeCommandHook->expects($i = self::exactly(count($testCases)))->method('onAfterHandle')->willReturnCallback(function (CommandInterface $command, PublishedEvents $events) use ($i, $testCases) {
            $caseIndex = $i->getInvocationCount() - 1;

            $testCase = $testCases[$caseIndex];
            self::assertEquals($testCase['command'], $command, sprintf('The command at step %s doesnt match as expected', $caseIndex));
            self::assertEquals($testCase['eventClassNames'], $events->map(fn ($event) => $event::class), sprintf('The events at step %s doesnt match as expected', $caseIndex));
            return Commands::createEmpty();
        });

        foreach ($testCases as $testCase) {
            $this->contentRepository->handle($testCase['command']);
        }
    }

    /**
     * onBeforeHandle can exchange the command that was passed by returning something else
     */
    public function testCommandHookReplacesCommand(): void
    {
        $command = CreateRootWorkspace::create(WorkspaceName::forLive(), ContentStreamId::fromString('cs-live'));

        $replacedCommand = CreateRootWorkspace::create(WorkspaceName::fromString('replaced'), ContentStreamId::fromString('cs-replaced'));
        $expectedEvents = PublishedEvents::fromArray([
            new ContentStreamWasCreated(ContentStreamId::fromString('cs-replaced')),
            new RootWorkspaceWasCreated(WorkspaceName::fromString('replaced'), ContentStreamId::fromString('cs-replaced'))
        ]);

        $this->fakeCommandHook->expects(self::once())->method('onBeforeHandle')->with($command)->willReturn($replacedCommand);
        $this->fakeCommandHook->expects(self::once())->method('onAfterHandle')->with($replacedCommand, $expectedEvents)->willReturn(Commands::createEmpty());

        $this->contentRepository->handle($command);

        self::assertNull($this->contentRepository->findWorkspaceByName(WorkspaceName::forLive()));
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('replaced')));
    }

    /**
     * Test for simple command handling with a followup, like issue a command on live a node was directly created on live - not published
     */
    public function testIssueFollowupCommandsSimpleCase(): void
    {
        $this->fakeCommandHook->expects(self::exactly(4))->method('onBeforeHandle')->willReturnArgument(0);
        $this->fakeCommandHook->expects($i = self::exactly(4))->method('onAfterHandle')->willReturnCallback(function (CommandInterface $command, PublishedEvents $events) use ($i) {
            if ($i->getInvocationCount() === 3) {
                self::assertInstanceOf(CreateNodeAggregateWithNode::class, $command);
                self::assertEquals([NodeAggregateWithNodeWasCreated::class], $events->map(fn ($event) => $event::class));

                $subgraph = $this->contentRepository->getContentGraph(WorkspaceName::forLive())->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::withoutRestrictions());
                $node = $subgraph->findNodeById(NodeAggregateId::fromString('document-node'));
                self::assertNotNull($node, 'The node must exist onAfterHandle');
                self::assertNull($node->getProperty('title'));

                return Commands::create(SetNodeProperties::create(
                    WorkspaceName::forLive(),
                    NodeAggregateId::fromString('document-node'),
                    OriginDimensionSpacePoint::createWithoutDimensions(),
                    PropertyValuesToWrite::fromArray([
                        'title' => 'set by hook'
                    ])
                ));
            } elseif ($i->getInvocationCount() === 4) {
                // recursion passes the via the previous onAfterHandle hook back here:
                self::assertInstanceOf(SetNodeProperties::class, $command);

                return Commands::createEmpty();
            } else {
                return Commands::createEmpty();
            }
        });

        $this->contentRepository->handle(CreateRootWorkspace::create(WorkspaceName::forLive(), ContentStreamId::fromString('cs-live')));
        $this->contentRepository->handle(CreateRootNodeAggregateWithNode::create(WorkspaceName::forLive(), NodeAggregateId::fromString('root'), NodeTypeName::fromString(NodeTypeName::ROOT_NODE_TYPE_NAME)));
        $this->contentRepository->handle(CreateNodeAggregateWithNode::create(
            WorkspaceName::forLive(),
            NodeAggregateId::fromString('document-node'),
            NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
            OriginDimensionSpacePoint::createWithoutDimensions(),
            parentNodeAggregateId: NodeAggregateId::fromString('root')
        ));

        $subgraph = $this->contentRepository->getContentGraph(WorkspaceName::forLive())->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::withoutRestrictions());
        $node = $subgraph->findNodeById(NodeAggregateId::fromString('document-node'));
        self::assertNotNull($node);
        self::assertEquals('set by hook', $node->getProperty('title'));
    }

    /**
     * Test for control-flow aware command handling with a followup, like issue a command on live if PublishWorkspace contains a certain creation of a node
     */
    public function testIssueFollowupCommandOnPublish(): void
    {
        $this->fakeCommandHook->expects(self::exactly(6))->method('onBeforeHandle')->willReturnArgument(0);
        $this->fakeCommandHook->expects($i = self::exactly(6))->method('onAfterHandle')->willReturnCallback(function (CommandInterface $command, PublishedEvents $events) use ($i) {
            if ($i->getInvocationCount() === 5) {
                self::assertInstanceOf(PublishWorkspace::class, $command);
                self::assertContains(NodeAggregateWithNodeWasCreated::class, $events->map(fn ($event) => $event::class));

                $subgraph = $this->contentRepository->getContentGraph(WorkspaceName::forLive())->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::withoutRestrictions());
                $node = $subgraph->findNodeById(NodeAggregateId::fromString('document-node'));
                self::assertNotNull($node, 'The node must exist onAfterHandle');
                self::assertNull($node->getProperty('title'));

                return Commands::create(SetNodeProperties::create(
                    WorkspaceName::forLive(),
                    NodeAggregateId::fromString('document-node'),
                    OriginDimensionSpacePoint::createWithoutDimensions(),
                    PropertyValuesToWrite::fromArray([
                        'title' => 'set by hook'
                    ])
                ));
            } elseif ($i->getInvocationCount() === 6) {
                // recursion passes the via the previous onAfterHandle hook back here:
                self::assertInstanceOf(SetNodeProperties::class, $command);

                return Commands::createEmpty();
            } else {
                return Commands::createEmpty();
            }
        });

        $this->contentRepository->handle(CreateRootWorkspace::create(WorkspaceName::forLive(), ContentStreamId::fromString('cs-live')));
        $this->contentRepository->handle(CreateRootNodeAggregateWithNode::create(WorkspaceName::forLive(), NodeAggregateId::fromString('root'), NodeTypeName::fromString(NodeTypeName::ROOT_NODE_TYPE_NAME)));
        $this->contentRepository->handle(CreateWorkspace::create(WorkspaceName::fromString('user'), WorkspaceName::forLive(), ContentStreamId::fromString('cs-user')));
        $this->contentRepository->handle(CreateNodeAggregateWithNode::create(
            WorkspaceName::fromString('user'),
            NodeAggregateId::fromString('document-node'),
            NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
            OriginDimensionSpacePoint::createWithoutDimensions(),
            parentNodeAggregateId: NodeAggregateId::fromString('root')
        ));
        $this->contentRepository->handle(PublishWorkspace::create(WorkspaceName::fromString('user')));

        $subgraph = $this->contentRepository->getContentGraph(WorkspaceName::forLive())->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::withoutRestrictions());
        $node = $subgraph->findNodeById(NodeAggregateId::fromString('document-node'));
        self::assertNotNull($node);
        self::assertEquals('set by hook', $node->getProperty('title'));
    }
}
