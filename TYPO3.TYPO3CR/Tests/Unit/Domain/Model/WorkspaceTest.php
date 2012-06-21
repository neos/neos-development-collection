<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the "Workspace" domain model
 *
 */
class WorkspaceTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function aWorkspaceCanBeBasedOnAnotherWorkspace() {
		$baseWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('BaseWorkspace');

		$workspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('MyWorkspace', $baseWorkspace);
		$this->assertSame('MyWorkspace', $workspace->getName());
		$this->assertSame($baseWorkspace, $workspace->getBaseWorkspace());
	}

	/**
	 * @test
	 */
	public function onInitializationANewlyCreatedWorkspaceCreatesItsOwnRootNode() {
		$workspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array(), '', FALSE);

		$mockNodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('add'), array(), '', FALSE);
		$mockNodeRepository->expects($this->once())->method('add');

		$workspace->_set('nodeRepository', $mockNodeRepository);

		$workspace->initializeObject(\TYPO3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);

		$this->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\Node', $workspace->getRootNode());
	}

	/**
	 * @test
	 */
	public function theCurrentContextCanBeAssignedAndRetrievedOfAWorkspace() {
		$mockContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);

		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('findOneByPath', 'getContext'), array(), '', FALSE);
		$nodeRepository->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));

		$mockRootNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);

		$workspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array(), '', FALSE);
		$workspace->_set('nodeRepository', $nodeRepository);
		$workspace->_set('rootNode', $mockRootNode);
		$workspace->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));

		$this->assertSame($mockContext, $workspace->getContext());
	}

	/**
	 * @test
	 */
	public function publishWillReplaceExistingNodesInBaseWorkspaceByNodeInWorkspaceToBePubslished() {
		$mockNodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('findByWorkspace', 'findOneByPath', 'remove', 'add'), array(), '', FALSE);

		$targetWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array(), '', FALSE);
		$existingNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);

		$currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('getPublishingTargetWorkspace'), array(), '', FALSE);
		$currentWorkspace->_set('nodeRepository', $mockNodeRepository);
		$currentWorkspace->expects($this->once())->method('getPublishingTargetWorkspace')->with('live')->will($this->returnValue($targetWorkspace));

		$nodesInCurrentWorkspace = array(
			$this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array('dummy'), array('/', $currentWorkspace)),
			$this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array('isRemoved', 'setWorkspace'), array('/sites/foo/homepage', $currentWorkspace)),
		);
		$nodesInCurrentWorkspace[1]->expects($this->once())->method('isRemoved')->will($this->returnValue(FALSE));
		$nodesInCurrentWorkspace[1]->expects($this->once())->method('setWorkspace')->with($targetWorkspace);

		$mockQueryResult = $this->getMock('TYPO3\FLOW3\Persistence\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('toArray')->will($this->returnValue($nodesInCurrentWorkspace));
		$mockNodeRepository->expects($this->once())->method('findByWorkspace')->will($this->returnValue($mockQueryResult));
		$mockNodeRepository->expects($this->once())->method('findOneByPath')->with('/sites/foo/homepage')->will($this->returnValue($existingNode));
		$mockNodeRepository->expects($this->once())->method('remove')->with($existingNode);

		$currentWorkspace->publish('live');
	}

	/**
	 * @test
	 */
	public function publishWillRemoveNodesInTargetWorkspaceIfTheyHaveBeenMarkedAsRemovedInSourceWorkspace() {
		$mockNodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('findByWorkspace', 'findOneByPath', 'remove', 'add'), array(), '', FALSE);

		$targetWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array(), '', FALSE);
		$existingNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);

		$currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('getPublishingTargetWorkspace'), array(), '', FALSE);
		$currentWorkspace->_set('nodeRepository', $mockNodeRepository);
		$currentWorkspace->expects($this->once())->method('getPublishingTargetWorkspace')->with('live')->will($this->returnValue($targetWorkspace));

		$nodesInCurrentWorkspace = array(
			$this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array('dummy'), array('/', $currentWorkspace)),
			$this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array('isRemoved'), array('/sites/foo/homepage', $currentWorkspace)),
		);
		$nodesInCurrentWorkspace[1]->expects($this->once())->method('isRemoved')->will($this->returnValue(TRUE));

		$mockQueryResult = $this->getMock('TYPO3\FLOW3\Persistence\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('toArray')->will($this->returnValue($nodesInCurrentWorkspace));
		$mockNodeRepository->expects($this->once())->method('findByWorkspace')->will($this->returnValue($mockQueryResult));
		$mockNodeRepository->expects($this->once())->method('findOneByPath')->with('/sites/foo/homepage')->will($this->returnValue($existingNode));
		$mockNodeRepository->expects($this->at(2))->method('remove')->with($existingNode);
		$mockNodeRepository->expects($this->at(3))->method('remove')->with($nodesInCurrentWorkspace[1]);

		$currentWorkspace->publish('live');
	}

	/**
	 * @test
	 */
	public function getNodeCountCallsRepositoryFunction() {
		$mockNodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('countByWorkspace'), array(), '', FALSE);

		$workspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array(), '', FALSE);
		$workspace->_set('nodeRepository', $mockNodeRepository);

		$mockNodeRepository->expects($this->once())->method('countByWorkspace')->with($workspace)->will($this->returnValue(42));

		$this->assertSame(42, $workspace->getNodeCount());
	}

	/**
	 * @test
	 */
	public function getPublishingTargetWorkspaceReturnsSpecifiedWorkspaceIfItIsABaseWorkspace() {
		$lowesWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('live');
		$currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array('user-foo', $lowesWorkspace));

		$actualTargetWorkspace = $currentWorkspace->_call('getPublishingTargetWorkspace', 'live');
		$this->assertSame($lowesWorkspace, $actualTargetWorkspace);

		$lowesWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('live');
		$intermediateWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('foo', $lowesWorkspace);
		$currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array('bar', $intermediateWorkspace));

		$actualTargetWorkspace = $currentWorkspace->_call('getPublishingTargetWorkspace', 'live');
		$this->assertSame($lowesWorkspace, $actualTargetWorkspace);

		$actualTargetWorkspace = $currentWorkspace->_call('getPublishingTargetWorkspace', 'foo');
		$this->assertSame($intermediateWorkspace, $actualTargetWorkspace);
	}

	/**
	 * @test
	 * @expectedException TYPO3\TYPO3CR\Exception\WorkspaceException
	 */
	public function getPublishingTargetWorkspaceThrowsAnExceptionIfWorkspaceIsNotBasedOnTheSpecifiedWorkspace() {
		$someBaseWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('live');
		$currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array('user-foo', $someBaseWorkspace));

		$currentWorkspace->_call('getPublishingTargetWorkspace', 'group-bar');
	}

	/**
	 * @return array
	 */
	public function validContextNodePaths() {
		return array(
			array('foo@user-bar'),
			array('foo/bar/baz@user-bar'),
			array('foo@user-UpperCamelCasedUser'),
			array('foo/bar/baz@user-UpperCamelCasedUser')
		);
	}

	/**
	 * @test
	 * @dataProvider validContextNodePaths
	 */
	public function contextNodePathMatchPatternMatchesNodeContextPaths($contextNodePath) {
		preg_match(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::MATCH_PATTERN_CONTEXTPATH, $contextNodePath, $matches);
		$this->assertArrayHasKey('WorkspaceName', $matches);
	}

	/**
	 * @return array
	 */
	public function invalidContextNodePaths() {
		return array(
			array('foo@user-bar.html'),
			array('foo/bar/baz')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidContextNodePaths
	 */
	public function contextNodePathMatchPatternDoesNotMatchInvalidNodeContextPaths($contextNodePath) {
		preg_match(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::MATCH_PATTERN_CONTEXTPATH, $contextNodePath, $matches);
		$this->assertArrayNotHasKey('WorkspaceName', $matches);
	}

}

?>
