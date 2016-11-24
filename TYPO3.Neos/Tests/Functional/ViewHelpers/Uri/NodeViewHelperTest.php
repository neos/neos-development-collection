<?php
namespace TYPO3\Neos\Tests\Functional\ViewHelpers\Uri;

/*
 * This file is part of the TYPO3.Neos package.
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
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView;
use TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation;

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
    protected $tsRuntime;

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
        $contextProperties = array(
            'workspaceName' => 'live'
        );
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
        $controllerContext = new ControllerContext(new ActionRequest($httpRequest), $requestHandler->getHttpResponse(), new Arguments(array()), new UriBuilder());
        $this->inject($this->viewHelper, 'controllerContext', $controllerContext);

        $typoScriptObject = $this->getAccessibleMock(TemplateImplementation::class, array('dummy'), array(), '', false);
        $this->tsRuntime = new Runtime(array(), $controllerContext);
        $this->tsRuntime->pushContextArray(array(
            'documentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home'),
            'alternativeDocumentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission')
        ));
        $this->inject($typoScriptObject, 'tsRuntime', $this->tsRuntime);
        $mockView = $this->getAccessibleMock(FluidView::class, array(), array(), '', false);
        $mockView->expects($this->any())->method('getTypoScriptObject')->will($this->returnValue($typoScriptObject));
        $viewHelperVariableContainer = new ViewHelperVariableContainer();
        $viewHelperVariableContainer->setView($mockView);
        $this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
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
        $this->tsRuntime->pushContext('documentNode', $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission'));
        $this->assertOutputLinkValid('en/home/about-us/history.html', $this->viewHelper->render('../history'));
        $this->tsRuntime->popContext();
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
        $mockView = $this->getAccessibleMock(TemplateView::class, array(), array(), '', false);
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
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render(null, null, false, array(), '', false, array(), 'alternativeDocumentNode'));
    }

    /**
     * @test
     */
    public function viewHelperRespectsArgumentsParameter()
    {
        $this->assertOutputLinkValid('en/home.html?foo=bar', $this->viewHelper->render('/sites/example/home@live', null, false, array('foo' => 'bar')));
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
