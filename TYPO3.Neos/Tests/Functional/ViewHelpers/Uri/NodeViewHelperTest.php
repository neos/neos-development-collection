<?php
namespace TYPO3\Neos\Tests\Functional\ViewHelpers\Uri;

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
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TypoScript\Core\Runtime;

/**
 * Functional test for the NodeViewHelper
 */
class NodeViewHelperTest extends FunctionalTestCase {

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

		$this->viewHelper = new \TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper();
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

		$this->assertOutputLinkValid('home.html', $this->viewHelper->render($targetNode));
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaAbsoluteNodePathString() {
		$this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('/sites/example/home'));
		$this->assertOutputLinkValid('en/home/about-us.html', $this->viewHelper->render('/sites/example/home/about-us'));
		$this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('/sites/example/home/about-us/mission'));
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaStringStartingWithTilde() {
		$this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('~'));
		$this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('~/home'));
		$this->assertOutputLinkValid('en/home/about-us.html', $this->viewHelper->render('~/home/about-us'));
		$this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('~/home/about-us/mission'));
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaStringPointingToSubNodes() {
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
	public function viewHelperRendersUriViaContextNodePathString() {
		$this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('/sites/example/home@live'));
		$this->assertOutputLinkValid('en/home/about-us.html', $this->viewHelper->render('/sites/example/home/about-us@live'));
		$this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('/sites/example/home/about-us/mission@live'));

		// The tests should also work in a regular fluid view, so we set that and repeat the tests
		$mockView = $this->getAccessibleMock('TYPO3\Fluid\View\TemplateView', array(), array(), '', FALSE);
		$viewHelperVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer();
		$viewHelperVariableContainer->setView($mockView);
		$this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
		$this->assertOutputLinkValid('en/home.html', $this->viewHelper->render('/sites/example/home@live'));
		$this->assertOutputLinkValid('en/home/about-us.html', $this->viewHelper->render('/sites/example/home/about-us@live'));
		$this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render('/sites/example/home/about-us/mission@live'));
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsAbsoluteParameter() {
		$this->assertOutputLinkValid('http://neos.test/en/home.html', $this->viewHelper->render(NULL, NULL, TRUE));
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsBaseNodeNameParameter() {
		$this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->viewHelper->render(NULL, NULL, FALSE, array(), '', FALSE, array(), 'alternativeDocumentNode'));
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsArgumentsParameter() {
		$this->assertOutputLinkValid('en/home.html?foo=bar', $this->viewHelper->render('/sites/example/home@live', NULL, FALSE, array('foo' => 'bar')));
	}

	/**
	 * @test
	 */
	public function viewHelperCatchesExceptionIfTargetNodeDoesNotExist() {
		$this->assertSame('', $this->viewHelper->render('/sites/example/non-existing-node'));
	}

	/**
	 * A wrapper function for the appropriate assertion for the Link- and its Uri-ViewHelper derivate.
	 * Is overridden in the FunctionalTest for the LinkViewHelper.
	 */
	protected function assertOutputLinkValid($expected, $actual) {
		$this->assertStringEndsWith($expected, $actual);
	}
}
