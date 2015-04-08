<?php
namespace TYPO3\Neos\Tests\Functional;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Functional test case which tests the renaming of nodes from within a workspace.
 *
 * Is placed here instead of the TYPO3CR package because that's where we have the correct
 * test fixtures in place.
 *
 * @group large
 */
class NodeRenamingTest extends AbstractNodeTest {

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $nodeInTestWorkspace;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$accessDecisionManager = $this->objectManager->get('TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface');
		$accessDecisionManager->setOverrideDecision(TRUE);

		$liveWorkspace = $this->node->getWorkspace();
		$personalWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('user-test', $liveWorkspace);
		$this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->add($personalWorkspace);
		$this->persistenceManager->persistAll();

		$this->nodeInTestWorkspace = $this->getNodeWithContextPath('/sites/example/home@user-test');
	}

	/**
	 * @test
	 */
	public function renamedNodeInPersonalWorkspaceDoesNotRenameInLiveWorkspace() {
		$teaserTestWorkspace = $this->nodeInTestWorkspace->getNode('teaser');
		$teaserTestWorkspace->setName('teaser-new');

		$this->assertNull($this->nodeInTestWorkspace->getNode('teaser/dummy42a'), 'renaming was not successful in user workspace');
		$this->assertNotNull($this->nodeInTestWorkspace->getNode('teaser-new/dummy42a'), 'renaming was not successful in user workspace (2)');

		$this->assertNotNull($this->node->getNode('teaser'),  'the renamed teaser should not shine through in the live workspace for subelements (1) ');
		$this->assertNotNull($this->node->getNode('teaser/dummy42a'), 'the renamed teaser should not shine through in the live workspace for subelements (2)');
		$this->assertNull($this->node->getNode('teaser-new/dummy42a'));
	}

	/**
	 * @test
	 */
	public function movedIntoInPersonalWorkspaceDoesNotAffectLiveWorkspace() {
		// move "teaser" into "main"
		$teaserTestWorkspace = $this->nodeInTestWorkspace->getNode('teaser');
		$teaserTestWorkspace->moveInto($this->nodeInTestWorkspace->getNode('main'));

		$this->assertNull($this->nodeInTestWorkspace->getNode('teaser/dummy42a'), 'moving not successful (1)');
		$this->assertNotNull($this->nodeInTestWorkspace->getNode('main/teaser/dummy42a'), 'moving not successful (2)');

		$this->assertNotNull($this->node->getNode('teaser'), 'moving shined through into live workspace (1)');
		$this->assertNotNull($this->node->getNode('teaser/dummy42a'), 'moving shined through into live workspace (2)');
		$this->assertNull($this->node->getNode('main/teaser/dummy42a'), 'moving shined through into live workspace (3)');
	}

	/**
	 * @test
	 */
	public function moveBeforeInPersonalWorkspaceDoesNotAffectLiveWorkspace() {
		// move "teaser" before "main/dummy42"
		$teaserTestWorkspace = $this->nodeInTestWorkspace->getNode('teaser');
		$teaserTestWorkspace->moveBefore($this->nodeInTestWorkspace->getNode('main/dummy42'));

		$this->assertNull($this->nodeInTestWorkspace->getNode('teaser/dummy42a'), 'moving not successful (1)');
		$this->assertNotNull($this->nodeInTestWorkspace->getNode('main/teaser/dummy42a'), 'moving not successful (2)');

		$this->assertNotNull($this->node->getNode('teaser'), 'moving shined through into live workspace (1)');
		$this->assertNotNull($this->node->getNode('teaser/dummy42a'), 'moving shined through into live workspace (2)');
		$this->assertNull($this->node->getNode('main/teaser/dummy42a'), 'moving shined through into live workspace (3)');
	}

	/**
	 * @test
	 */
	public function moveAfterInPersonalWorkspaceDoesNotAffectLiveWorkspace() {
		// move "teaser" after "main/dummy42"
		$teaserTestWorkspace = $this->nodeInTestWorkspace->getNode('teaser');
		$teaserTestWorkspace->moveAfter($this->nodeInTestWorkspace->getNode('main/dummy42'));

		$this->assertNull($this->nodeInTestWorkspace->getNode('teaser/dummy42a'), 'moving not successful (1)');
		$this->assertNotNull($this->nodeInTestWorkspace->getNode('main/teaser/dummy42a'), 'moving not successful (2)');

		$this->assertNotNull($this->node->getNode('teaser'), 'moving shined through into live workspace (1)');
		$this->assertNotNull($this->node->getNode('teaser/dummy42a'), 'moving shined through into live workspace (2)');
		$this->assertNull($this->node->getNode('main/teaser/dummy42a'), 'moving shined through into live workspace (3)');
	}
}
