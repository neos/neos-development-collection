<?php
namespace Neos\Neos\Tests\Functional\ViewHelpers\Uri;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\Helpers\FluidView;
use Neos\Fusion\FusionObjects\TemplateImplementation;
use Neos\Media\TypeConverter\AssetInterfaceConverter;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\ViewHelpers\Uri\NodeViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInvoker;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Functional test for the NodeViewHelper
 */
class NodeViewHelperTest extends FunctionalTestCase
{
    protected $testableSecurityEnabled = true;

    protected static $testablePersistenceEnabled = true;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var NodeViewHelper
     */
    protected $viewHelper;

    /**
     * @var Runtime
     */
    protected $runtime;

    /**
     * @var ContentContext
     */
    protected $contentContext;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var ViewHelperInvoker
     */
    protected $viewHelperInvoker;

    /**
     * @var RenderingContext
     */
    protected $renderingContext;

    public function setUp(): void
    {
        parent::setUp();
        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        $domainRepository = $this->objectManager->get(DomainRepository::class);
        $siteRepository = $this->objectManager->get(SiteRepository::class);
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contextProperties = [
            'workspaceName' => 'live'
        ];
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/../../Fixtures/NodeStructure.xml');
        $this->persistenceManager->persistAll();

        $currentDomain = $domainRepository->findOneByActiveRequest();
        if ($currentDomain !== null) {
            $contextProperties['currentSite'] = $currentDomain->getSite();
            $contextProperties['currentDomain'] = $currentDomain;
        } else {
            $contextProperties['currentSite'] = $siteRepository->findFirst();
        }
        $this->contentContext = $this->contextFactory->create($contextProperties);

        $this->viewHelper = new NodeViewHelper();

        /** @var $requestHandler \Neos\Flow\Tests\FunctionalTestRequestHandler */
        $requestHandler = self::$bootstrap->getActiveRequestHandler();
        $httpRequest = $requestHandler->getComponentContext()->getHttpRequest();
        $httpRequest = $httpRequest->withUri(new Uri('http://neos.test/'));
        $httpRequest = $httpRequest->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, RouteParameters::createEmpty()->withParameter('requestUriHost', 'neos.test'));
        $requestHandler->getComponentContext()->replaceHttpRequest($httpRequest);
        $controllerContext = new ControllerContext(ActionRequest::fromHttpRequest($httpRequest), new ActionResponse(), new Arguments([]), new UriBuilder());
        $this->inject($this->viewHelper, 'controllerContext', $controllerContext);

        $fusionObject = $this->getAccessibleMock(TemplateImplementation::class, ['dummy'], [], '', false);
        $this->runtime = new Runtime([], $controllerContext);
        $this->runtime->pushContextArray([
            'documentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home'),
            'alternativeDocumentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission')
        ]);
        $this->inject($fusionObject, 'runtime', $this->runtime);
        $mockView = $this->getAccessibleMock(FluidView::class, [], [], '', false);
        $mockView->expects(self::any())->method('getFusionObject')->willReturn($fusionObject);
        $viewHelperVariableContainer = new ViewHelperVariableContainer();
        $viewHelperVariableContainer->setView($mockView);

        $this->viewHelperInvoker = new ViewHelperInvoker();
        $this->renderingContext = new RenderingContext($mockView);
        $this->renderingContext->setViewHelperVariableContainer($viewHelperVariableContainer);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->inject($this->contextFactory, 'contextInstances', []);
        $this->inject($this->objectManager->get(AssetInterfaceConverter::class), 'resourcesAlreadyConvertedToAssets', []);
    }

    private function invoke(array $arguments = []): string
    {
        return $this->viewHelperInvoker->invoke($this->viewHelper, $arguments, $this->renderingContext);
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaGivenNodeObject(): void
    {
        $propertyMapper = $this->objectManager->get(PropertyMapper::class);
        $targetNode = $propertyMapper->convert('/sites/example/home', Node::class);

        $result = $this->invoke(['node' => $targetNode]);
        $this->assertOutputLinkValid('home.html', $result);
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaAbsoluteNodePathString(): void
    {
        $result = $this->invoke(['node' => '/sites/example/home']);
        $this->assertOutputLinkValid('en/home.html', $result);
        $result = $this->invoke(['node' => '/sites/example/home/about-us']);
        $this->assertOutputLinkValid('en/home/about-us.html', $result);
        $result = $this->invoke(['node' => '/sites/example/home/about-us/mission']);
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $result);
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaStringStartingWithTilde(): void
    {
        $result = $this->invoke(['node' => '~']);
        $this->assertOutputLinkValid('en/home.html', $result);
        $result = $this->invoke(['node' => '~/home']);
        $this->assertOutputLinkValid('en/home.html', $result);
        $result = $this->invoke(['node' => '~/home/about-us']);
        $this->assertOutputLinkValid('en/home/about-us.html', $result);
        $result = $this->invoke(['node' => '~/home/about-us/mission']);
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $result);
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaStringPointingToSubNodes(): void
    {
        $this->runtime->pushContext('documentNode', $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission'));
        $result = $this->invoke(['node' => '../history']);
        $this->assertOutputLinkValid('en/home/about-us/history.html', $result);
        $this->runtime->popContext();
        $result = $this->invoke(['node' => 'about-us/mission']);
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $result);
        $result = $this->invoke(['node' => './about-us/mission']);
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $result);
    }

    /**
     * We empty the TemplateVariableContainer for this test, as it shouldn't be needed for rendering a link to a node
     * identified by ContextNodePath
     *
     * @test
     */
    public function viewHelperRendersUriViaContextNodePathString(): void
    {
        $result = $this->invoke(['node' => '/sites/example/home@live']);
        $this->assertOutputLinkValid('en/home.html', $result);
        $result = $this->invoke(['node' => '/sites/example/home/about-us@live']);
        $this->assertOutputLinkValid('en/home/about-us.html', $result);
        $result = $this->invoke(['node' => '/sites/example/home/about-us/mission@live']);
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $result);

        // The tests should also work in a regular fluid view, so we set that and repeat the tests
        $mockView = $this->getAccessibleMock(TemplateView::class, [], [], '', false);
        $viewHelperVariableContainer = new ViewHelperVariableContainer();
        $viewHelperVariableContainer->setView($mockView);
        $this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
        $result = $this->invoke(['node' => '/sites/example/home@live']);
        $this->assertOutputLinkValid('en/home.html', $result);
        $result = $this->invoke(['node' => '/sites/example/home/about-us@live']);
        $this->assertOutputLinkValid('en/home/about-us.html', $result);
        $result = $this->invoke(['node' => '/sites/example/home/about-us/mission@live']);
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $result);
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaNodeUriPathString(): void
    {
        $result = $this->invoke(['node' => 'node://3239baee-3e7f-785c-0853-f4302ef32570']);
        $this->assertOutputLinkValid('en/home.html', $result);
        $result = $this->invoke(['node' => 'node://30e893c1-caef-0ca5-b53d-e5699bb8e506']);
        $this->assertOutputLinkValid('en/home/about-us.html', $result);
        $result = $this->invoke(['node' => 'node://63b28f4d-8831-ecb0-f9a6-466d97ffe2c2']);
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $result);
    }

    /**
     * @test
     */
    public function viewHelperRespectsAbsoluteParameter(): void
    {
        $result = $this->invoke(['absolute' => true]);
        $this->assertOutputLinkValid('http://neos.test/en/home.html', $result);
    }

    /**
     * @test
     */
    public function viewHelperRespectsBaseNodeNameParameter(): void
    {
        $result = $this->invoke(['baseNodeName' => 'alternativeDocumentNode']);
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $result);
    }

    /**
     * @test
     */
    public function viewHelperRespectsArgumentsParameter(): void
    {
        $result = $this->invoke([
            'node' => '/sites/example/home@live',
            'arguments' => ['foo' => 'bar']
        ]);
        $this->assertOutputLinkValid('en/home.html?foo=bar', $result);
    }

    /**
     * @test
     */
    public function viewHelperCatchesExceptionAndReturnsEmptyStringIfTargetNodeDoesNotExist(): void
    {
        $result = $this->invoke(['node' => '/sites/example/non-existing-node']);
        self::assertSame('', $result);
    }

    /**
     * A wrapper function for the appropriate assertion for the Link- and its Uri-ViewHelper derivate.
     * Is overridden in the FunctionalTest for the LinkViewHelper.
     *
     * @param string $expected
     * @param string $actual
     */
    protected function assertOutputLinkValid(string $expected, string $actual): void
    {
        self::assertStringEndsWith($expected, $actual);
    }
}
