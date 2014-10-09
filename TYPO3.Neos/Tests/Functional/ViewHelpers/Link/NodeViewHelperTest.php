<?php
namespace TYPO3\Neos\Tests\Functional\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\FlashMessageContainer;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\TypoScript\Core\Runtime;

/**
 * Functional test for the NodeViewHelper
 */
class NodeViewHelperTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	protected $testableSecurityEnabled = TRUE;

	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @var \TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var \TYPO3\TypoScript\Core\Runtime
	 */
	protected $tsRuntime;

	/**
	 * @var \TYPO3\Neos\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	public function setUp() {
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

		$currentDomain = $domainRepository->findOneByActiveRequest();
		if ($currentDomain !== NULL) {
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
		$httpRequest->setBaseUri('http://neos.test/');
		$controllerContext = new ControllerContext(new ActionRequest($httpRequest), $requestHandler->getHttpResponse(), new Arguments(array()), new UriBuilder(), new FlashMessageContainer());
		$this->inject($this->viewHelper, 'controllerContext', $controllerContext);

		$typoScriptObject = $this->getAccessibleMock('\TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation', array('dummy'), array(), '', FALSE);
		$this->tsRuntime = new Runtime(array(), $controllerContext);
		$this->tsRuntime->pushContextArray(array(
			'documentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home'),
			'alternativeDocumentNode' => $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission')
		));
		$this->inject($typoScriptObject, 'tsRuntime', $this->tsRuntime);
		$mockView = $this->getAccessibleMock('TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView', array(), array(), '', FALSE);
		$mockView->expects($this->any())->method('getTypoScriptObject')->will($this->returnValue($typoScriptObject));
		$viewHelperVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer();
		$viewHelperVariableContainer->setView($mockView);
		$this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
		$templateVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer(array());
		$this->inject($this->viewHelper, 'templateVariableContainer', $templateVariableContainer);
		$this->viewHelper->setRenderChildrenClosure(function() use ($templateVariableContainer) {
			return $templateVariableContainer->get('linkedNode')->getLabel();
		});
		$this->viewHelper->initialize();
	}

	public function tearDown() {
		parent::tearDown();

		$this->inject($this->contextFactory, 'contextInstances', array());
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaGivenNodeObject() {
		$targetNode = $this->propertyMapper->convert('/sites/example/home', 'TYPO3\TYPO3CR\Domain\Model\Node');

		$this->assertSame('<a href="/en/home.html">' . $targetNode->getLabel() . '</a>', $this->viewHelper->render($targetNode));
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaAbsoluteNodePathString() {
		$this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home'));
		$this->assertSame('<a href="/en/home/about-us.html">About Us Test</a>', $this->viewHelper->render('/sites/example/home/about-us'));
		$this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('/sites/example/home/about-us/mission'));
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaStringStartingWithTilde() {
		$this->assertSame('<a href="/">example.org</a>', $this->viewHelper->render('~'));
		$this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('~/home'));
		$this->assertSame('<a href="/en/home/about-us.html">About Us Test</a>', $this->viewHelper->render('~/home/about-us'));
		$this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('~/home/about-us/mission'));
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaStringPointingToSubNodes() {
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
	public function viewHelperRendersUriViaContextNodePathString() {
		$this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home@live'));
		$this->assertSame('<a href="/en/home/about-us.html">About Us Test</a>', $this->viewHelper->render('/sites/example/home/about-us@live'));
		$this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render('/sites/example/home/about-us/mission@live'));
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsAbsoluteParameter() {
		$this->assertSame('<a href="http://neos.test/en/home.html">Home</a>', $this->viewHelper->render(NULL, NULL, TRUE));
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsBaseNodeNameParameter() {
		$this->assertSame('<a href="/en/home/about-us/our-mission.html">Our mission</a>', $this->viewHelper->render(NULL, NULL, FALSE, array(), '', FALSE, array(), 'alternativeDocumentNode'));
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsArgumentsParameter() {
		$this->assertSame('<a href="/en/home.html?foo=bar">Home</a>', $this->viewHelper->render('/sites/example/home@live', NULL, FALSE, array('foo' => 'bar')));
	}

	/**
	 * @test
	 */
	public function viewHelperUsesNodeTitleIfEmpty() {
		$templateVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer(array());
		$this->inject($this->viewHelper, 'templateVariableContainer', $templateVariableContainer);
		$this->viewHelper->setRenderChildrenClosure(function() use ($templateVariableContainer) {
			return NULL;
		});
		$this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home@live'));
	}

	/**
	 * @test
	 */
	public function viewHelperAssignsLinkedNodeToNodeVariableName() {
		$templateVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer(array());
		$this->inject($this->viewHelper, 'templateVariableContainer', $templateVariableContainer);
		$this->viewHelper->setRenderChildrenClosure(function() use ($templateVariableContainer) {
			return $templateVariableContainer->get('alternativeLinkedNode')->getLabel();
		});
		$this->assertSame('<a href="/en/home.html">Home</a>', $this->viewHelper->render('/sites/example/home@live', NULL, FALSE, array(), '', FALSE, array(), 'documentNode', 'alternativeLinkedNode'));
	}

}
