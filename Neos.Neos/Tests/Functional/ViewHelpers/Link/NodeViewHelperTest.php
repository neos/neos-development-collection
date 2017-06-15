<?php
namespace Neos\Neos\Tests\Functional\ViewHelpers\Link;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\FluidAdaptor\Core\ViewHelper\TemplateVariableContainer;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use Neos\FluidAdaptor\View\AbstractTemplateView;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Media\TypeConverter\AssetInterfaceConverter;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\ViewHelpers\Link\NodeViewHelper;
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
        $this->router->setRoutesConfiguration(null);
        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        $domainRepository = $this->objectManager->get(DomainRepository::class);
        $siteRepository = $this->objectManager->get(SiteRepository::class);
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contextProperties = array(
            'workspaceName' => 'live'
        );
        $contentContext = $this->contextFactory->create($contextProperties);
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/../../Fixtures/NodeStructure.xml', $contentContext);
        $this->persistenceManager->persistAll();

        /** @var Domain $currentDomain */
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
        /** @var $requestHandler FunctionalTestRequestHandler */
        $requestHandler = self::$bootstrap->getActiveRequestHandler();
        $httpRequest = $requestHandler->getHttpRequest();
        $httpRequest->setBaseUri(new Uri('http://neos.test/'));
        $controllerContext = new ControllerContext(new ActionRequest($httpRequest), $requestHandler->getHttpResponse(), new Arguments(array()), new UriBuilder());
        $this->inject($this->viewHelper, 'controllerContext', $controllerContext);

        $fusionObject = $this->getAccessibleMock(TemplateImplementation::class, array('dummy'), array(), '', false);
        $this->runtime = new Runtime(array(), $controllerContext);
        $this->runtime->pushContextArray(array(
            'documentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home'),
            'alternativeDocumentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission')
        ));
        $this->inject($fusionObject, 'runtime', $this->runtime);
        /** @var AbstractTemplateView|\PHPUnit_Framework_MockObject_MockObject $mockView */
        $mockView = $this->getAccessibleMock(FluidView::class, array(), array(), '', false);
        $mockView->expects($this->any())->method('getFusionObject')->will($this->returnValue($fusionObject));
        $viewHelperVariableContainer = new ViewHelperVariableContainer();
        $viewHelperVariableContainer->setView($mockView);
        $this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
        $templateVariableContainer = new TemplateVariableContainer(array());
        $this->inject($this->viewHelper, 'templateVariableContainer', $templateVariableContainer);
        $this->viewHelper->setRenderChildrenClosure(function () use ($templateVariableContainer) {
            $linkedNode = $templateVariableContainer->get('linkedNode');
            return $linkedNode !== null ? $linkedNode->getLabel() : '';
        });
        $this->viewHelper->initialize();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->inject($this->contextFactory, 'contextInstances', array());
        $this->inject($this->objectManager->get(AssetInterfaceConverter::class), 'resourcesAlreadyConvertedToAssets', array());
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaGivenNodeObject()
    {
        $targetNode = $this->propertyMapper->convert('/sites/example/home', Node::class);

        $this->assertSame('<a href="/en/home.html">' . $targetNode->getLabel() . '</a>', $this->viewHelper->render($targetNode));
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaAbsoluteNodePathString()
    {
        $this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home'));
        $this->assertSame('<a href="/en/home/about-us.html">About Us Test</a>', $this->viewHelper->render('/sites/example/home/about-us'));
        $this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('/sites/example/home/about-us/mission'));
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaStringStartingWithTilde()
    {
        $this->assertSame('<a href="/en/home.html">example.org</a>', $this->viewHelper->render('~'));
        $this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('~/home'));
        $this->assertSame('<a href="/en/home/about-us.html">About Us Test</a>', $this->viewHelper->render('~/home/about-us'));
        $this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('~/home/about-us/mission'));
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaStringPointingToSubNodes()
    {
        $this->runtime->pushContext('documentNode', $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission'));
        $this->assertSame('<a href="/en/home/about-us/history.html">History</a>', $this->viewHelper->render('../history'));
        $this->runtime->popContext();
        $this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('about-us/mission'));
        $this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('./about-us/mission'));
    }

    /**
     * We empty the TemplateVariableContainer for this test, as it shouldn't be needed for rendering a link to a node
     * identified by ContextNodePath
     *
     * @test
     */
    public function viewHelperRendersUriViaContextNodePathString()
    {
        $this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home@live'));
        $this->assertSame('<a href="/en/home/about-us.html">About Us Test</a>', $this->viewHelper->render('/sites/example/home/about-us@live'));
        $this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('/sites/example/home/about-us/mission@live'));

        // The tests should also work in a regular fluid view, so we set that and repeat the tests
        $mockView = $this->getAccessibleMock(TemplateView::class, array(), array(), '', false);
        $viewHelperVariableContainer = new ViewHelperVariableContainer();
        $viewHelperVariableContainer->setView($mockView);
        $this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
        $this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home@live'));
        $this->assertSame('<a href="/en/home/about-us.html">About Us Test</a>', $this->viewHelper->render('/sites/example/home/about-us@live'));
        $this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('/sites/example/home/about-us/mission@live'));
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaNodeUriPathString()
    {
        $this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('node://3239baee-3e7f-785c-0853-f4302ef32570'));
        $this->assertSame('<a href="/en/home/about-us.html">About Us Test</a>', $this->viewHelper->render('node://30e893c1-caef-0ca5-b53d-e5699bb8e506'));
        $this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('node://63b28f4d-8831-ecb0-f9a6-466d97ffe2c2'));
    }

    /**
     * @test
     */
    public function viewHelperRespectsAbsoluteParameter()
    {
        $this->assertSame('<a href="http://neos.test/en/home.html">Home</a>', $this->viewHelper->render(null, null, true));
    }

    /**
     * @test
     */
    public function viewHelperRespectsBaseNodeNameParameter()
    {
        $this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render(null, null, false, array(), '', false, array(), 'alternativeDocumentNode'));
    }

    /**
     * @test
     */
    public function viewHelperRespectsArgumentsParameter()
    {
        $this->assertSame('<a href="/en/home.html?foo=bar">Home</a>', $this->viewHelper->render('/sites/example/home@live', null, false, array('foo' => 'bar')));
    }

    /**
     * @test
     */
    public function viewHelperCatchesExceptionExceptionIfTargetNodeDoesNotExist()
    {
        $this->assertSame('<a></a>', $this->viewHelper->render('/sites/example/non-existing-node'));
    }

    /**
     * @test
     */
    public function viewHelperResolvesLinksToChildNodeShortcutPages()
    {
        $this->assertSame('<a href="/en/home/shortcuts/shortcut-to-child-node/child-node.html">Shortcut to child node</a>', $this->viewHelper->render('/sites/example/home/shortcuts/shortcut-to-child-node'));
    }

    /**
     * @test
     */
    public function viewHelperResolvesLinksToParentNodeShortcutPages()
    {
        $this->assertSame('<a href="/en/home/shortcuts.html">Shortcut to parent node</a>', $this->viewHelper->render('/sites/example/home/shortcuts/shortcut-to-parent-node'));
    }

    /**
     * @test
     */
    public function viewHelperResolvesLinksToTargetNodeShortcutPages()
    {
        $this->assertSame('<a href="/en/home/shortcuts/shortcut-to-child-node/target-node.html">Shortcut to target node</a>', $this->viewHelper->render('/sites/example/home/shortcuts/shortcut-to-target-node'));
    }

    /**
     * @test
     */
    public function viewHelperResolvesLinksToUriShortcutPages()
    {
        $this->assertSame('<a href="/en/home/shortcuts/shortcut-to-child-node/target-node.html">Shortcut to target node</a>', $this->viewHelper->render('/sites/example/home/shortcuts/shortcut-to-target-node'));
    }

    /**
     * @test
     */
    public function viewHelperUsesNodeTitleIfEmpty()
    {
        $templateVariableContainer = new TemplateVariableContainer(array());
        $this->inject($this->viewHelper, 'templateVariableContainer', $templateVariableContainer);
        $this->viewHelper->setRenderChildrenClosure(function () use ($templateVariableContainer) {
            return null;
        });
        $this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home@live'));
    }

    /**
     * @test
     */
    public function viewHelperAssignsLinkedNodeToNodeVariableName()
    {
        $templateVariableContainer = new TemplateVariableContainer(array());
        $this->inject($this->viewHelper, 'templateVariableContainer', $templateVariableContainer);
        $this->viewHelper->setRenderChildrenClosure(function () use ($templateVariableContainer) {
            return $templateVariableContainer->get('alternativeLinkedNode')->getLabel();
        });
        $this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home@live', null, false, array(), '', false, array(), 'documentNode', 'alternativeLinkedNode'));
    }
}
