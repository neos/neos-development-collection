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

use TYPO3\Flow\Reflection\ObjectAccess;

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
	 * @var string
	 */
	protected $currentTestWorkspaceName;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->currentTestWorkspaceName = uniqid('user-', TRUE);
		$this->setUpRootNodeAndRepository();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$this->saveNodesAndTearDownRootNodeAndRepository();
		parent::tearDown();

	}

	protected function setUpRootNodeAndRepository() {
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$personalContext = $this->contextFactory->create(array('workspaceName' => $this->currentTestWorkspaceName));
		$this->nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
		$this->rootNode = $personalContext->getNode('/');

		$this->persistenceManager->persistAll();
	}

	public function saveNodesAndTearDownRootNodeAndRepository() {
		$this->nodeDataRepository->flushNodeRegistry();
		$nodeFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Factory\NodeFactory');
		ObjectAccess::setProperty($nodeFactory, 'nodes', array(), TRUE);
		$this->inject($this->contextFactory, 'contextInstances', array());
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
		$this->nodeDataRepository = NULL;
		$this->rootNode = NULL;
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

		$this->saveNodesAndTearDownRootNodeAndRepository();
		$this->setUpRootNodeAndRepository();

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

	/**
	 * We set up the following node structure:
	 *
	 * rootNode
	 *     |
	 *   parentNode
	 *  |          |
	 * childNodeA  childNodeB
	 *               |
	 *             childNodeC
	 *
	 * We then move childNodeB UNDERNEATH childNodeA and check that it does not shine through
	 * when directly asking parentNode for childNodeB.
	 *
	 * @test
	 */
	public function nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldBeRemovedFromOriginalLocation() {
		$parentNode = $this->rootNode->createNode('parentNode');
		$parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->createNode('childNodeC');
		$parentNode->getWorkspace()->publish('live');

		$this->saveNodesAndTearDownRootNodeAndRepository();
		$this->setUpRootNodeAndRepository();

		$parentNode2 = $this->rootNode->getNode('parentNode');

		$this->assertSame($parentNode->getIdentifier(), $parentNode2->getIdentifier());
		$childNodeA2 = $parentNode2->getNode('childNodeA');
		$this->assertNotNull($childNodeA2, 'Child node A must be there');
		$childNodeB2 = $parentNode2->getNode('childNodeB');
		$this->assertNotNull($childNodeB2, 'Child node B must be there');
		$childNodeB2->moveInto($childNodeA2);

		$this->saveNodesAndTearDownRootNodeAndRepository();
		$this->setUpRootNodeAndRepository();

		$parentNode3 = $this->rootNode->getNode('parentNode');
		//$this->assertNotSame($parentNode2, $parentNode3);
		$childNodeB3 = $parentNode3->getNode('childNodeB');
		$this->assertTrue($childNodeB3 === NULL, 'child node B should not shine through as it has been moved.');
	}

	/**
	 * For test setup / node structure, see nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldBeRemovedFromOriginalLocation
	 *
	 * @test
	 */
	public function nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldBeRemovedFromOriginalLocationWhileIteratingOverIt() {
		$rootNode = $this->rootNode;
		$rootNodeWorkspace = $this->rootNode->getWorkspace();
		$parentNode = $this->rootNode->createNode('parentNode1');
		$childNodeA = $parentNode->createNode('childNode1A');
		$childNodeB = $parentNode->createNode('childNode1B');
		$childNodeB->createNode('childNode1C');
		$parentNode->getWorkspace()->publish('live');

		$this->saveNodesAndTearDownRootNodeAndRepository();
		$this->setUpRootNodeAndRepository();

		$this->assertNotSame($rootNode, $this->rootNode);
		$this->assertNotSame($rootNodeWorkspace, $this->rootNode->getWorkspace(), 'Workspace is not correctly cleaned up.');
		$parentNode2 = $this->rootNode->getNode('parentNode1');
		$this->assertNotSame($parentNode, $parentNode2);
		$this->assertSame('live', $parentNode2->getWorkspace()->getName());
		$childNodeA2 = $parentNode2->getNode('childNode1A');
		$this->assertNotNull($childNodeA2, 'Child node A must be there');
		$this->assertNotSame($childNodeA, $childNodeA2);
		$childNodeB2 = $parentNode2->getNode('childNode1B');
		$this->assertNotNull($childNodeB2, 'Child node B must be there');
		$this->assertNotSame($childNodeB, $childNodeB2);

		$childNodeB2->moveInto($childNodeA2);

		$this->saveNodesAndTearDownRootNodeAndRepository();
		$this->setUpRootNodeAndRepository();

		$parentNode3 = $this->rootNode->getNode('parentNode1');
		$childNodes = $parentNode3->getChildNodes();
		$this->assertSame(1, count($childNodes), 'parentNode is only allowed to have a single child node (childNode1A).');
	}

	/**
	 * For test setup / node structure, see nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldBeRemovedFromOriginalLocation
	 *
	 * Here, we move childNodeC underneath childNodeA.
	 *
	 * @test
	 */
	public function nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldWorkWhenUsingPrimaryChildNode() {
		$parentNode = $this->rootNode->createNode('parentNode');
		$parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->createNode('childNodeC');
		$parentNode->getWorkspace()->publish('live');

		$this->saveNodesAndTearDownRootNodeAndRepository();
		$this->setUpRootNodeAndRepository();

		$childNodeC2 = $this->rootNode->getNode('parentNode/childNodeB/childNodeC');
		$childNodeA2 = $this->rootNode->getNode('parentNode/childNodeA');
		$childNodeC2->moveInto($childNodeA2);

		$this->saveNodesAndTearDownRootNodeAndRepository();
		$this->setUpRootNodeAndRepository();

		$childNodeB3 = $this->rootNode->getNode('parentNode/childNodeB');
		$this->assertNull($childNodeB3->getPrimaryChildNode());
		$childNodeA3 = $this->rootNode->getNode('parentNode/childNodeA');
		$childNodeC3 = $childNodeA3->getPrimaryChildNode();
		$this->assertNotNull($childNodeC3);
	}
}

?>