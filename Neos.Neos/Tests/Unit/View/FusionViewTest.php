<?php
namespace Neos\Neos\Tests\Unit\View;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollectionInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Fusion\Core\FusionConfiguration;
use Neos\Fusion\Core\FusionGlobals;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Neos\Domain\Model\FusionRenderingStuff;
use Neos\Neos\Domain\Model\RenderingContext;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Domain\Service\RenderingUtility;
use Neos\Neos\Exception;
use Neos\Neos\View\FusionView;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class FusionViewTest extends TestCase
{
    private FusionView $fusionView;

    private RuntimeFactory $runtimeFactoryMock;
    private RenderingUtility $renderingUtilityMock;
    private FusionService $fusionServiceMock;

    private ControllerContext $controllerContextMock;
    private ActionRequest $actionRequestMock;

    public function setUp(): void
    {
        $this->runtimeFactoryMock = $this->getMockBuilder(RuntimeFactory::class)->disableOriginalConstructor()->getMock();
        $this->renderingUtilityMock = $this->getMockBuilder(RenderingUtility::class)->disableOriginalConstructor()->getMock();
        $this->fusionServiceMock = $this->getMockBuilder(FusionService::class)->disableOriginalConstructor()->getMock();

        $this->actionRequestMock = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->controllerContextMock = new ControllerContext(
            $this->actionRequestMock,
            new ActionResponse(),
            new Arguments(),
            $this->getMockBuilder(UriBuilder::class)->disableOriginalConstructor()->getMock()
        );

        $this->fusionView = new FusionView(
            [],
            $this->runtimeFactoryMock,
            $this->renderingUtilityMock,
            $this->fusionServiceMock
        );
        $this->fusionView->setControllerContext($this->controllerContextMock);
    }

    /** @test */
    public function attemptToRenderWithoutNodeInformationAtAllThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("FusionView needs a variable 'value' set with a Node object.");

        $this->fusionView->render();
    }

    /** @test */
    public function attemptToRenderWithInvalidNodeInformationThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("FusionView needs a variable 'value' set with a Node object.");

        $this->fusionView->assign('value', 'foo');
        // validated before $this->fusionView->render(); is called!
    }

    /** @test */
    public function basicRender(): void
    {
        $renderingContext = new RenderingContext(
            $entryNode = $this->createNodeMock(),
            $this->createNodeMock(),
            $this->createNodeMock(),
        );

        $fusionGlobals = FusionGlobals::fromArray(['request' => $this->actionRequestMock]);

        $fusionRenderingStuff = new FusionRenderingStuff(
            $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock(),
            $renderingContext,
            $fusionGlobals
        );

        $fusionConfiguration = FusionConfiguration::fromArray([]);

        $this->renderingUtilityMock->expects(self::once())->method('createFusionRenderingStuff')->with($entryNode, $this->actionRequestMock)
            ->willReturn($fusionRenderingStuff);

        $this->fusionServiceMock->expects(self::once())->method('createFusionConfigurationFromSite')->with($fusionRenderingStuff->site)
            ->willReturn($fusionConfiguration);

        $runtimeMock = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $this->runtimeFactoryMock->expects(self::once())->method('createFromConfiguration')
            ->with($fusionConfiguration, $fusionGlobals)
            ->willReturn($runtimeMock);

        $this->fusionView->assign('value', $entryNode);

        $runtimeMock->expects(self::once())->method('render')->with('root')->willReturn('Hello Welt');

        $runtimeMock->expects(self::once())->method('canRender')->with('root')->willReturn(true);

        // assert the correct context is made available in the runtime
        $runtimeMock->expects(self::once())->method('pushContextArray')->with($renderingContext->toContextArray());

        $runtimeMock->expects(self::once())->method('setControllerContext')->with($this->controllerContextMock);

        $runtimeMock->expects(self::once())->method('popContext');

        self::assertTrue($this->fusionView->canRenderWithNodeAndPath());

        self::assertEquals('root', $this->fusionView->getFusionPath());

        $output = $this->fusionView->render();

        self::assertEquals('Hello Welt', $output);
    }

    /** @test */
    public function renderMergesHttpResponseIfOutputIsHttpMessage(): void
    {
        $renderingContext = new RenderingContext(
            $entryNode = $this->createNodeMock(),
            $this->createNodeMock(),
            $this->createNodeMock(),
        );

        $fusionGlobals = FusionGlobals::fromArray(['request' => $this->actionRequestMock]);

        $fusionRenderingStuff = new FusionRenderingStuff(
            $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock(),
            $renderingContext,
            $fusionGlobals
        );

        $fusionConfiguration = FusionConfiguration::fromArray([]);

        $this->renderingUtilityMock->expects(self::once())->method('createFusionRenderingStuff')->with($entryNode, $this->actionRequestMock)
            ->willReturn($fusionRenderingStuff);

        $this->fusionServiceMock->expects(self::once())->method('createFusionConfigurationFromSite')->with($fusionRenderingStuff->site)
            ->willReturn($fusionConfiguration);

        $runtimeMock = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $this->runtimeFactoryMock->expects(self::once())->method('createFromConfiguration')
            ->with($fusionConfiguration, $fusionGlobals)
            ->willReturn($runtimeMock);

        $this->fusionView->assign('value', $entryNode);

        $runtimeMock->expects(self::any())->method('render')->will(self::returnValue(<<<EOF
        HTTP/1.1 200 OK
        Content-Type: application/json

        Message body
        EOF));

        $output = $this->fusionView->render();

        self::assertInstanceOf(ResponseInterface::class, $output);
        self::assertEquals(
            ['Content-Type' => ['application/json']],
            $output->getHeaders()
        );
        self::assertEquals('Message body', $output->getBody()->getContents());
    }

    /** @test */
    public function consecutiveRender(): void
    {
        $renderingContext = new RenderingContext(
            $entryNode = $this->createNodeMock(),
            $this->createNodeMock(),
            $this->createNodeMock(),
        );

        $fusionGlobals = FusionGlobals::fromArray(['request' => $this->actionRequestMock]);

        $fusionRenderingStuff = new FusionRenderingStuff(
            $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock(),
            $renderingContext,
            $fusionGlobals
        );

        $fusionConfiguration = FusionConfiguration::fromArray([]);

        $this->renderingUtilityMock->expects(self::exactly(2))->method('createFusionRenderingStuff')->with($entryNode, $this->actionRequestMock)
            ->willReturn($fusionRenderingStuff);

        $this->fusionServiceMock->expects(self::exactly(2))->method('createFusionConfigurationFromSite')->with($fusionRenderingStuff->site)
            ->willReturn($fusionConfiguration);

        $firstRuntimeMock = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $secondRuntimeMock = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $this->runtimeFactoryMock->expects(self::exactly(2))->method('createFromConfiguration')
            ->with($fusionConfiguration, $fusionGlobals)
            ->willReturn($firstRuntimeMock, $secondRuntimeMock);

        $this->fusionView->assign('value', $entryNode);

        $firstRuntimeMock->expects(self::once())->method('render')->with('root')->willReturn('Hello Welt');

        $firstOutput = $this->fusionView->render();

        self::assertEquals('Hello Welt', $firstOutput);

        $this->fusionView->setFusionPath('root2');

        self::assertEquals('root2', $this->fusionView->getFusionPath());

        $this->fusionView->assign('value', $entryNode);

        $secondRuntimeMock->expects(self::once())->method('render')->with('root2')->willReturn('Hello Welt 2');

        $secondOutput = $this->fusionView->render();

        self::assertEquals('Hello Welt 2', $secondOutput);
    }

    private function createNodeMock(): Node
    {
        return new Node(
            ContentSubgraphIdentity::create(
                ContentRepositoryId::fromString("cr"),
                ContentStreamId::fromString("cs"),
                DimensionSpacePoint::fromArray([]),
                VisibilityConstraints::withoutRestrictions()
            ),
            $nodeAggregateId ?? NodeAggregateId::fromString("na"),
            OriginDimensionSpacePoint::fromArray([]),
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            NodeTypeName::fromString("nt"),
            $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(PropertyCollectionInterface::class)->getMock(),
            NodeName::fromString("nn"),
            Timestamps::create($now = new \DateTimeImmutable(), $now, null, null)
        );
    }
}
