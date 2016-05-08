<?php
namespace TYPO3\Neos\Tests\Functional\ViewHelpers\Link;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer;
use TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use TYPO3\Fluid\View\AbstractTemplateView;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TypoScript\Core\Runtime;

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
        $this->nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
        $domainRepository = $this->objectManager->get('TYPO3\Neos\Domain\Repository\DomainRepository');
        $siteRepository = $this->objectManager->get('TYPO3\Neos\Domain\Repository\SiteRepository');
        $this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
        $contextProperties = array(
            'workspaceName' => 'live'
        );
        $contentContext = $this->contextFactory->create($contextProperties);
        $siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
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

        $this->propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');

        $this->viewHelper = new \TYPO3\Neos\ViewHelpers\Link\NodeViewHelper();
        /** @var $requestHandler \TYPO3\Flow\Tests\FunctionalTestRequestHandler */
        $requestHandler = self::$bootstrap->getActiveRequestHandler();
        $httpRequest = $requestHandler->getHttpRequest();
        $httpRequest->setBaseUri(new Uri('http://neos.test/'));
        $controllerContext = new ControllerContext(new ActionRequest($httpRequest), $requestHandler->getHttpResponse(), new Arguments(array()), new UriBuilder());
        $this->inject($this->viewHelper, 'controllerContext', $controllerContext);

        $typoScriptObject = $this->getAccessibleMock('\TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation', array('dummy'), array(), '', false);
        $this->tsRuntime = new Runtime(array(), $controllerContext);
        $this->tsRuntime->pushContextArray(array(
            'documentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home'),
            'alternativeDocumentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission')
        ));
        $this->inject($typoScriptObject, 'tsRuntime', $this->tsRuntime);
        /** @var AbstractTemplateView|\PHPUnit_Framework_MockObject_MockObject $mockView */
        $mockView = $this->getAccessibleMock('TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView', array(), array(), '', false);
        $mockView->expects($this->any())->method('getTypoScriptObject')->will($this->returnValue($typoScriptObject));
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
        $this->inject($this->objectManager->get('TYPO3\Media\TypeConverter\AssetInterfaceConverter'), 'resourcesAlreadyConvertedToAssets', array());
    }

    /**
     * @test
     */
    public function viewHelperRendersUriViaGivenNodeObject()
    {
        $targetNode = $this->propertyMapper->convert('/sites/example/home', 'TYPO3\TYPO3CR\Domain\Model\Node');

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
        $this->tsRuntime->pushContext('documentNode', $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission'));
        $this->assertSame('<a href="/en/home/about-us/history.html">History</a>', $this->viewHelper->render('../history'));
        $this->tsRuntime->popContext();
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
        $mockView = $this->getAccessibleMock('TYPO3\Fluid\View\TemplateView', array(), array(), '', false);
        $viewHelperVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer();
        $viewHelperVariableContainer->setView($mockView);
        $this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
        $this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home@live'));
        $this->assertSame('<a href="/en/home/about-us.html">About Us Test</a>', $this->viewHelper->render('/sites/example/home/about-us@live'));
        $this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('/sites/example/home/about-us/mission@live'));
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
