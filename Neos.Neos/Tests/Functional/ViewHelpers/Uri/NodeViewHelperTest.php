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

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Media\TypeConverter\AssetInterfaceConverter;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\ViewHelpers\Uri\NodeViewHelper;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\Helpers\FluidView;
use Neos\Fusion\FusionObjects\TemplateImplementation;

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
     * @var PropertyMapper
     */
    protected $propertyMapper;

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

    public function setUp()
    {
        parent::setUp();
        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        $domainRepository = $this->objectManager->get(DomainRepository::class);
        $siteRepository = $this->objectManager->get(SiteRepository::class);
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contextProperties = [
            'workspaceName' => 'live'
        ];
        $contentContext = $this->contextFactory->create($contextProperties);
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/../../Fixtures/NodeStructure.xml', $contentContext);
        $this->persistenceManager->persistAll();

        $currentDomain = $domainRepository->findOneByActiveRequest();
        if ($currentDomain !== null) {
            $contextProperties['currentSite'] = $currentDomain->getSite();
            $contextProperties['currentDomain'] = $currentDomain;
        } else {
            $contextProperties['currentSite'] = $siteRepository->findFirst();
        }
        $contentContext = $this->contextFactory->create($contextProperties);

        $this->contentContext = $contentContext;

        $this->propertyMapper = $this->objectManager->get(PropertyMapper::class);

        $this->viewHelper = new NodeViewHelper();
        /** @var $requestHandler \Neos\Flow\Tests\FunctionalTestRequestHandler */
        $requestHandler = self::$bootstrap->getActiveRequestHandler();
        $httpRequest = $requestHandler->getHttpRequest();
        $httpRequest->setBaseUri('http://neos.test/');
        $controllerContext = new ControllerContext(new ActionRequest($httpRequest), $requestHandler->getHttpResponse(), new Arguments([]), new UriBuilder());
        $this->inject($this->viewHelper, 'controllerContext', $controllerContext);

        $fusionObject = $this->getAccessibleMock(TemplateImplementation::class, ['dummy'], [], '', false);
        $this->runtime = new Runtime([], $controllerContext);
        $this->runtime->pushContextArray([
            'documentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home'),
            'alternativeDocumentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission')
        ]);
        $this->inject($fusionObject, 'runtime', $this->runtime);
        $mockView = $this->getAccessibleMock(FluidView::class, [], [], '', false);
        $mockView->expects($this->any())->method('getFusionObject')->will($this->returnValue($fusionObject));
        $viewHelperVariableContainer = new ViewHelperVariableContainer();
        $viewHelperVariableContainer->setView($mockView);
        $this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->inject($this->contextFactory, 'contextInstances', []);
        $this->inject($this->objectManager->get(AssetInterfaceConverter::class), 'resourcesAlreadyConvertedToAssets', []);
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaGivenNodeObject()
    {
        $targetNode = $this->propertyMapper->convert('/sites/example/home', Node::class);

        $this->assertOutputLinkValid('home.html', $this->viewHelper->render($targetNode));
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaAbsoluteNodePathString()
    {
        $this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('/sites/example/home'));
        $this->assertOutputLinkValid('en/home/about-us.html', $this->viewHelper->render('/sites/example/home/about-us'));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('/sites/example/home/about-us/mission'));
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaStringStartingWithTilde()
    {
        $this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('~'));
        $this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('~/home'));
        $this->assertOutputLinkValid('en/home/about-us.html', $this->viewHelper->render('~/home/about-us'));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('~/home/about-us/mission'));
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaStringPointingToSubNodes()
    {
        $this->runtime->pushContext('documentNode', $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission'));
        $this->assertOutputLinkValid('en/home/about-us/history.html', $this->viewHelper->render('../history'));
        $this->runtime->popContext();
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('about-us/mission'));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('./about-us/mission'));
    }

    /**
     * We empty the TemplateVariableContainer for this test, as it shouldn't be needed for rendering a link to a node
     * identified by ContextNodePath
     *
     * @test
     */
    public function viewHelperRendersUriViaContextNodePathString()
    {
        $this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('/sites/example/home@live'));
        $this->assertOutputLinkValid('en/home/about-us.html', $this->viewHelper->render('/sites/example/home/about-us@live'));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('/sites/example/home/about-us/mission@live'));

        // The tests should also work in a regular fluid view, so we set that and repeat the tests
        $mockView = $this->getAccessibleMock(TemplateView::class, [], [], '', false);
        $viewHelperVariableContainer = new ViewHelperVariableContainer();
        $viewHelperVariableContainer->setView($mockView);
        $this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
        $this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('/sites/example/home@live'));
        $this->assertOutputLinkValid('en/home/about-us.html', $this->viewHelper->render('/sites/example/home/about-us@live'));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('/sites/example/home/about-us/mission@live'));
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaNodeUriPathString()
    {
        $this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('node://3239baee-3e7f-785c-0853-f4302ef32570'));
        $this->assertOutputLinkValid('en/home/about-us.html', $this->viewHelper->render('node://30e893c1-caef-0ca5-b53d-e5699bb8e506'));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('node://63b28f4d-8831-ecb0-f9a6-466d97ffe2c2'));
    }

    /**
     * @test
     */
    public function viewHelperRespectsAbsoluteParameter()
    {
        $this->assertOutputLinkValid('http://neos.test/en/home.html', $this->viewHelper->render(null, null, true));
    }

    /**
     * @test
     */
    public function viewHelperRespectsBaseNodeNameParameter()
    {
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render(null, null, false, [], '', false, [], 'alternativeDocumentNode'));
    }

    /**
     * @test
     */
    public function viewHelperRespectsArgumentsParameter()
    {
        $this->assertOutputLinkValid('en/home.html?foo=bar', $this->viewHelper->render('/sites/example/home@live', null, false, ['foo' => 'bar']));
    }

    /**
     * @test
     */
    public function viewHelperCatchesExceptionIfTargetNodeDoesNotExist()
    {
        $this->assertSame('', $this->viewHelper->render('/sites/example/non-existing-node'));
    }

    /**
     * A wrapper function for the appropriate assertion for the Link- and its Uri-ViewHelper derivate.
     * Is overridden in the FunctionalTest for the LinkViewHelper.
     */
    protected function assertOutputLinkValid($expected, $actual)
    {
        $this->assertStringEndsWith($expected, $actual);
    }
}
