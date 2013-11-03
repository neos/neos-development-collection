<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

/**
 * Testcase for the "Workspace" domain model
 *
 */
class WorkspaceTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function aWorkspaceCanBeBasedOnAnotherWorkspace() {
		$baseWorkspace = new Workspace('BaseWorkspace');

		$workspace = new Workspace('MyWorkspace', $baseWorkspace);
		$this->assertSame('MyWorkspace', $workspace->getName());
		$this->assertSame($baseWorkspace, $workspace->getBaseWorkspace());
	}

	/**
	 * @test
	 */
	public function onInitializationANewlyCreatedWorkspaceCreatesItsOwnRootNode() {
		$workspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array(), '', FALSE);

		$mockNodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('add'), array(), '', FALSE);
		$mockNodeDataRepository->expects($this->once())->method('add');

		$workspace->_set('nodeDataRepository', $mockNodeDataRepository);

		$workspace->initializeObject(\TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);

		$this->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\NodeData', $workspace->getRootNodeData());
	}

	/**
	 * @test
	 */
	public function publishWillReplaceExistingNodesInBaseWorkspaceByNodeInWorkspaceToBePublished() {
		$liveWorkspace = new Workspace('live');
		$currentWorkspace = new Workspace('current', $liveWorkspace);

		$mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->setMethods(array('findOneByIdentifier', 'remove', 'add'))->getMock();
		$this->inject($currentWorkspace, 'nodeDataRepository', $mockNodeDataRepository);

		$mockPublishingService = $this->getMockBuilder('TYPO3\Neos\Service\PublishingService')->disableOriginalConstructor()->getMock();
		$this->inject($currentWorkspace, 'publishingService', $mockPublishingService);

		$existingNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

		$mockNode1 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->disableOriginalConstructor()->getMock();
		$mockNode1->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('/'));

		$mockNode2 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->disableOriginalConstructor()->getMock();
		$mockNode2->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('/sites/foo/homepage'));
		$mockNode2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('fakeUuid'));
		$mockNode2->expects($this->once())->method('isRemoved')->will($this->returnValue(FALSE));
		$mockNode2->expects($this->once())->method('setWorkspace')->with($liveWorkspace);
		$nodesInCurrentWorkspace = array($mockNode1, $mockNode2);

		$mockPublishingService->expects($this->once())->method('getUnpublishedNodes')->will($this->returnValue($nodesInCurrentWorkspace));
		$mockNodeDataRepository->expects($this->once())->method('findOneByIdentifier')->with('fakeUuid', $liveWorkspace)->will($this->returnValue($existingNode));
		$mockNodeDataRepository->expects($this->once())->method('remove')->with($existingNode);

		$currentWorkspace->publish($liveWorkspace);
	}

	/**
	 * @test
	 */
	public function publishWillRemoveNodesInTargetWorkspaceIfTheyHaveBeenMarkedAsRemovedInSourceWorkspace() {
		$liveWorkspace = new Workspace('live');
		$currentWorkspace = new Workspace('current', $liveWorkspace);

		$mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->setMethods(array('findOneByIdentifier', 'remove', 'add'))->getMock();
		$this->inject($currentWorkspace, 'nodeDataRepository', $mockNodeDataRepository);

		$mockPublishingService = $this->getMockBuilder('TYPO3\Neos\Service\PublishingService')->disableOriginalConstructor()->getMock();
		$this->inject($currentWorkspace, 'publishingService', $mockPublishingService);

		$existingNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

		$mockNode1 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->disableOriginalConstructor()->getMock();
		$mockNode1->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('/'));

		$mockNode2 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->disableOriginalConstructor()->getMock();
		$mockNode2->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('/sites/foo/homepage'));
		$mockNode2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('fakeUuid'));
		$mockNode2->expects($this->once())->method('isRemoved')->will($this->returnValue(TRUE));
		$nodesInCurrentWorkspace = array($mockNode1, $mockNode2);

		$mockPublishingService->expects($this->once())->method('getUnpublishedNodes')->will($this->returnValue($nodesInCurrentWorkspace));
		$mockNodeDataRepository->expects($this->once())->method('findOneByIdentifier')->with('fakeUuid')->will($this->returnValue($existingNode));
		$mockNodeDataRepository->expects($this->at(1))->method('remove')->with($existingNode);
		$mockNodeDataRepository->expects($this->at(2))->method('remove')->with($mockNode2);

		$currentWorkspace->publish($liveWorkspace);
	}

	/**
	 * @test
	 */
	public function getNodeCountCallsRepositoryFunction() {
		$mockNodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('countByWorkspace'), array(), '', FALSE);

		$workspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array(), '', FALSE);
		$workspace->_set('nodeDataRepository', $mockNodeDataRepository);

		$mockNodeDataRepository->expects($this->once())->method('countByWorkspace')->with($workspace)->will($this->returnValue(42));

		$this->assertSame(42, $workspace->getNodeCount());
	}

	/**
	 * @test
	 */
	public function verifyPublishingTargetWorkspaceDoesNotThrowAnExceptionIfTargetWorkspaceIsABaseWorkspace() {
		$someBaseWorkspace = new Workspace('live');
		$reviewWorkspace = new Workspace('review', $someBaseWorkspace);
		$currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array('user-foo', $reviewWorkspace));

		$currentWorkspace->_call('verifyPublishingTargetWorkspace', $reviewWorkspace);
		$currentWorkspace->_call('verifyPublishingTargetWorkspace', $someBaseWorkspace);
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\WorkspaceException
	 */
	public function verifyPublishingTargetWorkspaceThrowsAnExceptionIfWorkspaceIsNotBasedOnTheSpecifiedWorkspace() {
		$someBaseWorkspace = new Workspace('live');
		$currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array('user-foo', $someBaseWorkspace));
		$otherWorkspace = new Workspace('user-bar', $someBaseWorkspace);

		$currentWorkspace->_call('verifyPublishingTargetWorkspace', $otherWorkspace);
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
