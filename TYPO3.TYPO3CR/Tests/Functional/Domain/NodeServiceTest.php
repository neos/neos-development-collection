<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Service\NodeService;

/**
 * Functional test case which should cover all NodeService behavior.
 */
class NodeServiceTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected $context;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository
	 */
	protected $contentDimensionRepository;

	/**
	 * @var NodeService
	 */
	protected $nodeService;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->nodeDataRepository = new \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository();
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$this->context = $this->contextFactory->create(array('workspaceName' => 'live'));
		$this->nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$this->contentDimensionRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
		$this->nodeService = $this->objectManager->get(NodeService::class);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->inject($this->contextFactory, 'contextInstances', array());
		$this->contentDimensionRepository->setDimensionsConfiguration(array());
	}

	/**
	 * @test
	 */
	public function nodePathAvailableForNodeWillReturnFalseIfNodeWithGivenPathExistsAlready() {
		$rootNode = $this->context->getRootNode();

		$fooNode = $rootNode->createNode('foo');
		$fooNode->createNode('bar');
		$bazNode = $rootNode->createNode('baz');
		$this->persistenceManager->persistAll();

		$actualResult = $this->nodeService->nodePathAvailableForNode('/foo/bar', $bazNode);
		$this->assertFalse($actualResult);
	}
}
