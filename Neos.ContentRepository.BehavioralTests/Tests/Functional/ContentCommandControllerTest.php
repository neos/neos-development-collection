<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional;

use Neos\ContentRepository\BehavioralTests\Tests\Functional\Extensibility\AbstractExtensibilityTestCase;
use Neos\ContentRepository\Core\CommandHandler\Commands;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\Command\ContentCommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Cli\Response;
use Neos\Utility\ObjectAccess;
use Symfony\Component\Console\Output\BufferedOutput;

// FIXME, like ContentRepositoryMaintenanceCommandControllerTest this test should reside in Neos.ContentRepositoryRegistry,
// but requires the test case infrastructure of this package which is a dev dependency
final class ContentCommandControllerTest extends AbstractExtensibilityTestCase
{
    private ContentCommandController $contentController;

    private Response $response;

    private BufferedOutput $bufferedOutput;

    /** @before */
    public function injectController(): void
    {
        $this->contentController = $this->getObject(ContentCommandController::class);

        $this->response = new Response();
        $this->bufferedOutput = new BufferedOutput();

        ObjectAccess::setProperty($this->contentController, 'response', $this->response, true);
        ObjectAccess::getProperty($this->contentController, 'output', true)->setOutput($this->bufferedOutput);
    }

    private function setUpContentStructure(): void
    {
        $this->fakeCommandHook->method('onBeforeHandle')->willReturnArgument(0);
        $this->fakeCommandHook->method('onAfterHandle')->willReturn(Commands::createEmpty());

        $this->contentRepository->handle(CreateRootWorkspace::create(WorkspaceName::forLive(), ContentStreamId::fromString('cs-live')));
        $this->contentRepository->handle(CreateRootNodeAggregateWithNode::create(WorkspaceName::forLive(), NodeAggregateId::fromString('root'), NodeTypeName::fromString(NodeTypeName::ROOT_NODE_TYPE_NAME)));
        $this->contentRepository->handle(CreateNodeAggregateWithNode::create(WorkspaceName::forLive(), NodeAggregateId::fromString('document-a'), NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'), OriginDimensionSpacePoint::createWithoutDimensions(), NodeAggregateId::fromString('root')));
        $this->contentRepository->handle(CreateNodeAggregateWithNode::create(WorkspaceName::forLive(), NodeAggregateId::fromString('document-a-child'), NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'), OriginDimensionSpacePoint::createWithoutDimensions(), NodeAggregateId::fromString('document-a')));
    }

    private function findNodeById(string $nodeAggregateId): ?Node
    {
        return $this->contentRepository->getContentGraph(WorkspaceName::forLive())
            ->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty())
            ->findNodeById(NodeAggregateId::fromString($nodeAggregateId));
    }

    /** @test */
    public function tagSubtreeTagsTheNodeAndItsDescendants(): void
    {
        $this->setUpContentStructure();

        $this->contentController->tagSubtreeCommand('document-a', 'restricted-area', static::$contentRepositoryId->value);

        self::assertStringContainsString('Tagged node aggregate "document-a" with "restricted-area"', $this->bufferedOutput->fetch());

        $taggedNode = $this->findNodeById('document-a');
        self::assertNotNull($taggedNode);
        self::assertTrue($taggedNode->tags->withoutInherited()->contain(SubtreeTag::fromString('restricted-area')));

        $childNode = $this->findNodeById('document-a-child');
        self::assertNotNull($childNode);
        self::assertTrue($childNode->tags->contain(SubtreeTag::fromString('restricted-area')));
        self::assertFalse($childNode->tags->withoutInherited()->contain(SubtreeTag::fromString('restricted-area')));
    }

    /** @test */
    public function untagSubtreeRemovesAnExplicitTag(): void
    {
        $this->setUpContentStructure();

        $this->contentController->tagSubtreeCommand('document-a', 'restricted-area', static::$contentRepositoryId->value);
        $this->contentController->untagSubtreeCommand('document-a', 'restricted-area', static::$contentRepositoryId->value);

        self::assertStringContainsString('Removed tag "restricted-area" from node aggregate "document-a"', $this->bufferedOutput->fetch());

        $untaggedNode = $this->findNodeById('document-a');
        self::assertNotNull($untaggedNode);
        self::assertFalse($untaggedNode->tags->contain(SubtreeTag::fromString('restricted-area')));

        $childNode = $this->findNodeById('document-a-child');
        self::assertNotNull($childNode);
        self::assertFalse($childNode->tags->contain(SubtreeTag::fromString('restricted-area')));
    }

    /** @test */
    public function tagSubtreeWithInvalidTagFails(): void
    {
        $this->setUpContentStructure();

        try {
            $this->contentController->tagSubtreeCommand('document-a', 'Not A Valid Tag!', static::$contentRepositoryId->value);
            self::fail('Expected the command to stop');
        } catch (StopCommandException) {
        }

        self::assertSame(1, $this->response->getExitCode());
        self::assertStringContainsString('does not adhere', $this->bufferedOutput->fetch());
    }

    /** @test */
    public function tagSubtreeWithUnknownNodeAggregateFails(): void
    {
        $this->setUpContentStructure();

        try {
            $this->contentController->tagSubtreeCommand('i-do-not-exist', 'restricted-area', static::$contentRepositoryId->value);
            self::fail('Expected the command to stop');
        } catch (StopCommandException) {
        }

        self::assertSame(1, $this->response->getExitCode());
        self::assertStringContainsString('Node aggregate "i-do-not-exist" does not exist in workspace "live"', $this->bufferedOutput->fetch());
    }

    /** @test */
    public function untagSubtreeWithoutExplicitTagFails(): void
    {
        $this->setUpContentStructure();

        try {
            $this->contentController->untagSubtreeCommand('document-a', 'restricted-area', static::$contentRepositoryId->value);
            self::fail('Expected the command to stop');
        } catch (StopCommandException) {
        }

        self::assertSame(1, $this->response->getExitCode());
        self::assertStringContainsString('is not explicitly tagged', $this->bufferedOutput->fetch());
    }
}
