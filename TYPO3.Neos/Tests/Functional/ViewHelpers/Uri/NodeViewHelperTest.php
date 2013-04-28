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
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Service\ContentContext;

/**
 */
class NodeViewHelperTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	protected $testableSecurityEnabled = TRUE;
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @var \TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->nodeRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeRepository');
		ObjectAccess::setProperty($this->nodeRepository, 'context', new ContentContext('live'), TRUE);
		$this->nodeRepository->getContext()->setCurrentSite(new Site('example'));
		$siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
		$siteImportService->importSitesFromFile(__DIR__ . '/../../Fixtures/NodeStructure.xml');
		$this->persistenceManager->persistAll();

		$this->propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');

		$this->viewHelper = new \TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper();
		/** @var $requestHandler \TYPO3\Flow\Tests\FunctionalTestRequestHandler */
		$requestHandler = self::$bootstrap->getActiveRequestHandler();
		$controllerContext = new ControllerContext(new ActionRequest($requestHandler->getHttpRequest()), $requestHandler->getHttpResponse(), new Arguments(array()), new UriBuilder(), new FlashMessageContainer());
		$this->inject($this->viewHelper, 'controllerContext', $controllerContext);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaGivenNodeObject() {
		$this->nodeRepository->getContext()->setCurrentNode($this->propertyMapper->convert('/sites/example/home/about-us/mission', 'TYPO3\TYPO3CR\Domain\Model\Node'));
		$targetNode = $this->propertyMapper->convert('/sites/example/home', 'TYPO3\TYPO3CR\Domain\Model\Node');

		$this->assertOutputLinkValid('home.html', $this->viewHelper->render($targetNode));
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaAbsoluteNodePathString() {
		$this->nodeRepository->getContext()->setCurrentNode($this->propertyMapper->convert('/sites/example/home/about-us/mission', 'TYPO3\TYPO3CR\Domain\Model\Node'));
		$this->assertOutputLinkValid('home.html', $this->viewHelper->render('/sites/example/home'));
		$this->assertOutputLinkValid('home/about-us.html', $this->viewHelper->render('/sites/example/home/about-us'));
		$this->assertOutputLinkValid('home/about-us/mission.html', $this->viewHelper->render('/sites/example/home/about-us/mission'));
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaStringStartingWithTilde() {
		$this->nodeRepository->getContext()->setCurrentNode($this->propertyMapper->convert('/sites/example/home/about-us/mission', 'TYPO3\TYPO3CR\Domain\Model\Node'));
		$this->assertOutputLinkValid('home.html', $this->viewHelper->render('~/home'));
		$this->assertOutputLinkValid('home/about-us.html', $this->viewHelper->render('~/home/about-us'));
		$this->assertOutputLinkValid('home/about-us/mission.html', $this->viewHelper->render('~/home/about-us/mission'));
	}

	/**
	 * @test
	 */
	public function viewHelperRendersUriViaStringPointingToSubNodes() {
		$this->nodeRepository->getContext()->setCurrentNode($this->propertyMapper->convert('/sites/example/home/about-us/mission', 'TYPO3\TYPO3CR\Domain\Model\Node'));
		$this->assertOutputLinkValid('home/about-us/history.html', $this->viewHelper->render('../history'));

		$this->nodeRepository->getContext()->setCurrentNode($this->propertyMapper->convert('/sites/example/home', 'TYPO3\TYPO3CR\Domain\Model\Node'));
		$this->assertOutputLinkValid('home/about-us/mission.html', $this->viewHelper->render('about-us/mission'));
		$this->assertOutputLinkValid('home/about-us/mission.html', $this->viewHelper->render('./about-us/mission'));
	}

	/**
	 * A wrapper function for the appropriate assertion for the Link- and its Uri-ViewHelper derivate.
	 * Is overridden in the FunctionalTest for the LinkViewHelper.
	 */
	protected function assertOutputLinkValid($expected, $actual) {
		$this->assertStringEndsWith($expected, $actual);
	}
}
?>