<?php
namespace Neos\Neos\Tests\Functional;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Security\Authorization\TestingPrivilegeManager;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;

/**
 * Functional test case which tests the renaming of nodes from within a workspace.
 *
 * Is placed here instead of the ContentRepository package because that's where we have the correct
 * test fixtures in place.
 *
 * @group large
 */
class NodeRenamingTest extends AbstractNodeTest
{
    /**
     * @var \Neos\ContentRepository\Domain\Model\NodeInterface
     */
    protected $nodeInTestWorkspace;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $privilegeManager = $this->objectManager->get(TestingPrivilegeManager::class);
        $privilegeManager->setOverrideDecision(true);

        $liveWorkspace = $this->node->getWorkspace();
        $personalWorkspace = new Workspace('user-test', $liveWorkspace);
        $this->objectManager->get(WorkspaceRepository::class)->add($personalWorkspace);
        $this->persistenceManager->persistAll();

        $this->nodeInTestWorkspace = $this->getNodeWithContextPath('/sites/example/home@user-test');
    }

    /**
     * @test
     */
    public function renamedNodeInPersonalWorkspaceDoesNotRenameInLiveWorkspace()
    {
        $teaserTestWorkspace = $this->nodeInTestWorkspace->getNode('teaser');
        $teaserTestWorkspace->setName('teaser-new');

        self::assertNull($this->nodeInTestWorkspace->getNode('teaser/dummy46a'), 'renaming was not successful in user workspace');
        self::assertNotNull($this->nodeInTestWorkspace->getNode('teaser-new/dummy46a'), 'renaming was not successful in user workspace (2)');

        self::assertNotNull($this->node->getNode('teaser'), 'the renamed teaser should not shine through in the live workspace for subelements (1) ');
        self::assertNotNull($this->node->getNode('teaser/dummy46a'), 'the renamed teaser should not shine through in the live workspace for subelements (2)');
        self::assertNull($this->node->getNode('teaser-new/dummy46a'));
    }

    /**
     * @test
     */
    public function movedIntoInPersonalWorkspaceDoesNotAffectLiveWorkspace()
    {
        // move headline of "teaser" into "main"
        $teaserTestWorkspace = $this->nodeInTestWorkspace->getNode('teaser/dummy46a');
        $teaserTestWorkspace->moveInto($this->nodeInTestWorkspace->getNode('main'));

        self::assertNull($this->nodeInTestWorkspace->getNode('teaser/dummy46a'), 'moving not successful (1)');
        self::assertNotNull($this->nodeInTestWorkspace->getNode('main/dummy46a'), 'moving not successful (2)');

        self::assertNotNull($this->node->getNode('teaser'), 'moving shined through into live workspace (1)');
        self::assertNotNull($this->node->getNode('teaser/dummy46a'), 'moving shined through into live workspace (2)');
        self::assertNull($this->node->getNode('main/dummy46a'), 'moving shined through into live workspace (3)');
    }

    /**
     * @test
     */
    public function moveBeforeInPersonalWorkspaceDoesNotAffectLiveWorkspace()
    {
        // move headline of "teaser" before "main/dummy42"
        $teaserTestWorkspace = $this->nodeInTestWorkspace->getNode('teaser/dummy46a');
        $teaserTestWorkspace->moveBefore($this->nodeInTestWorkspace->getNode('main/dummy42'));

        self::assertNull($this->nodeInTestWorkspace->getNode('teaser/dummy46a'), 'moving not successful (1)');
        self::assertNotNull($this->nodeInTestWorkspace->getNode('main/dummy46a'), 'moving not successful (2)');

        self::assertNotNull($this->node->getNode('teaser'), 'moving shined through into live workspace (1)');
        self::assertNotNull($this->node->getNode('teaser/dummy46a'), 'moving shined through into live workspace (2)');
        self::assertNull($this->node->getNode('main/dummy46a'), 'moving shined through into live workspace (3)');
    }

    /**
     * @test
     */
    public function moveAfterInPersonalWorkspaceDoesNotAffectLiveWorkspace()
    {
        // move headline of "teaser" after "main/dummy42"
        $teaserTestWorkspace = $this->nodeInTestWorkspace->getNode('teaser/dummy46a');
        $teaserTestWorkspace->moveAfter($this->nodeInTestWorkspace->getNode('main/dummy42'));

        self::assertNull($this->nodeInTestWorkspace->getNode('teaser/dummy46a'), 'moving not successful (1)');
        self::assertNotNull($this->nodeInTestWorkspace->getNode('main/dummy46a'), 'moving not successful (2)');

        self::assertNotNull($this->node->getNode('teaser'), 'moving shined through into live workspace (1)');
        self::assertNotNull($this->node->getNode('teaser/dummy46a'), 'moving shined through into live workspace (2)');
        self::assertNull($this->node->getNode('main/dummy46a'), 'moving shined through into live workspace (3)');
    }
}
