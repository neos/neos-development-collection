<?php
namespace TYPO3\Neos\Tests\Functional\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\FlashMessageContainer;
use TYPO3\Flow\Mvc\Routing\UriBuilder;

/**
 * Testcase for the LinkingService
 */
class LinkingServiceTest extends FunctionalTestCase {

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
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var \TYPO3\Neos\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @var \TYPO3\Neos\Service\LinkingService
	 */
	protected $linkingService;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $baseNode;

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
		$siteImportService->importSitesFromFile(__DIR__ . '/../Fixtures/NodeStructure.xml', $contentContext);
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

		$this->baseNode = $this->contentContext->getCurrentSiteNode()->getNode('home');

		$this->linkingService = $this->objectManager->get('TYPO3\Neos\Service\LinkingService');
		/** @var $requestHandler \TYPO3\Flow\Tests\FunctionalTestRequestHandler */
		$requestHandler = self::$bootstrap->getActiveRequestHandler();
		$this->controllerContext = new ControllerContext(new ActionRequest($requestHandler->getHttpRequest()), $requestHandler->getHttpResponse(), new Arguments(array()), new UriBuilder(), new FlashMessageContainer());
	}

	public function tearDown() {
		parent::tearDown();
		$this->inject($this->contextFactory, 'contextInstances', array());
	}

	/**
	 * @test
	 */
	public function linkingServiceCreatesUriViaGivenNodeObject() {
		$targetNode = $this->propertyMapper->convert('/sites/example/home', 'TYPO3\TYPO3CR\Domain\Model\Node');

		$this->assertOutputLinkValid('home.html', $this->linkingService->createNodeUri($this->controllerContext, $targetNode));
	}

	/**
	 * @test
	 */
	public function linkingServiceCreatesUriViaAbsoluteNodePathString() {
		$this->assertOutputLinkValid('home.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home', $this->baseNode));
		$this->assertOutputLinkValid('home/about-us.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/about-us', $this->baseNode));
		$this->assertOutputLinkValid('home/about-us/mission.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/about-us/mission', $this->baseNode));
	}

	/**
	 * @test
	 */
	public function linkingServiceCreatesUriViaStringStartingWithTilde() {
		$this->assertOutputLinkValid('/', $this->linkingService->createNodeUri($this->controllerContext, '~', $this->baseNode));
		$this->assertOutputLinkValid('home.html', $this->linkingService->createNodeUri($this->controllerContext, '~/home', $this->baseNode));
		$this->assertOutputLinkValid('home/about-us.html', $this->linkingService->createNodeUri($this->controllerContext, '~/home/about-us', $this->baseNode));
		$this->assertOutputLinkValid('home/about-us/mission.html', $this->linkingService->createNodeUri($this->controllerContext, '~/home/about-us/mission', $this->baseNode));
	}

	/**
	 * @test
	 */
	public function linkingServiceCreatesUriViaStringPointingToSubNodes() {
		$this->assertOutputLinkValid('home/about-us/history.html', $this->linkingService->createNodeUri($this->controllerContext, '../history', $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission')));
		$this->assertOutputLinkValid('home/about-us/mission.html', $this->linkingService->createNodeUri($this->controllerContext, 'about-us/mission', $this->baseNode));
		$this->assertOutputLinkValid('home/about-us/mission.html', $this->linkingService->createNodeUri($this->controllerContext, './about-us/mission', $this->baseNode));
	}

	/**
	 * We empty the TemplateVariableContainer for this test, as it shouldn't be needed for rendering a link to a node
	 * identified by ContextNodePath
	 *
	 * @test
	 */
	public function linkingServiceCreatesUriViaContextNodePathString() {
		$this->assertOutputLinkValid('home.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home@live'));
		$this->assertOutputLinkValid('home/about-us.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/about-us@live'));
		$this->assertOutputLinkValid('home/about-us/mission.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/about-us/mission@live'));
	}

	/**
	 * A wrapper function for the appropriate assertion for the Link- and its Uri-ViewHelper derivate.
	 * Is overridden in the FunctionalTest for the LinkViewHelper.
	 */
	protected function assertOutputLinkValid($expected, $actual) {
		$this->assertStringEndsWith($expected, $actual);
	}
}