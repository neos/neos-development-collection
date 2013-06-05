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

/**
 * Functional test case which covers all workspace-related behavior of the
 * content repository.
 *
 */
class WorkspacesTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\Node
	 */
	protected $rootNode;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$personalContext = $this->contextFactory->create(array('workspaceName' => 'user-robert'));
		$this->nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
		$this->rootNode = $personalContext->getNode('/');
		$this->persistenceManager->persistAll();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->inject($this->contextFactory, 'contextInstances', array());
	}

	/**
	 * @test
	 */
	public function nodesCreatedInAPersonalWorkspacesCanBeRetrievedAgainInThePersonalContext() {
		$fooNode = $this->rootNode->createNode('foo');
		$this->assertSame($fooNode, $this->rootNode->getNode('foo'));

		$this->persistenceManager->persistAll();

		$this->assertSame($fooNode, $this->rootNode->getNode('foo'));
	}

	/**
	 * @test
	 */
	public function nodesCreatedInAPersonalWorkspaceAreNotVisibleInTheLiveWorkspace() {
		$this->rootNode->createNode('homepage')->createNode('about');
		$this->persistenceManager->persistAll();

		$liveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
		$liveRootNode = $liveContext->getRootNode();

		$this->assertNull($liveRootNode->getNode('/homepage/about'));
	}

	/**
	 * @test
	 */
	public function evenWithoutPersistAllNodesCreatedInAPersonalWorkspaceAreNotVisibleInTheLiveWorkspace() {
		$this->rootNode->createNode('homepage')->createNode('imprint');

		$liveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
		$liveRootNode = $liveContext->getRootNode();

		$this->assertNull($liveRootNode->getNode('/homepage/imprint'));
	}
}

?>