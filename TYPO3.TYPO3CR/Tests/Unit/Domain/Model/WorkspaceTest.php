<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Service\NodeService;

/**
 * Test case for the "Workspace" domain model
 *
 */
class WorkspaceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function aWorkspaceCanBeBasedOnAnotherWorkspace()
    {
        $baseWorkspace = new Workspace('BaseWorkspace');

        $workspace = new Workspace('MyWorkspace', $baseWorkspace);
        $this->assertSame('MyWorkspace', $workspace->getName());
        $this->assertSame($baseWorkspace, $workspace->getBaseWorkspace());
    }

    /**
     * @test
     */
    public function onInitializationANewlyCreatedWorkspaceCreatesItsOwnRootNode()
    {
        $workspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array(), '', false);

        $mockNodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('add'), array(), '', false);
        $mockNodeDataRepository->expects($this->once())->method('add');

        $workspace->_set('nodeDataRepository', $mockNodeDataRepository);

        $workspace->initializeObject(\TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);

        $this->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\NodeData', $workspace->getRootNodeData());
    }

    /**
     * @test
     */
    public function getNodeCountCallsRepositoryFunction()
    {
        $mockNodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('countByWorkspace'), array(), '', false);

        $workspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array(), '', false);
        $workspace->_set('nodeDataRepository', $mockNodeDataRepository);

        $mockNodeDataRepository->expects($this->once())->method('countByWorkspace')->with($workspace)->will($this->returnValue(42));

        $this->assertSame(42, $workspace->getNodeCount());
    }

    /**
     * @test
     */
    public function publishNodeReturnsIfTheCurrentWorkspaceHasNoBaseWorkspace()
    {
        $targetWorkspace = new Workspace('live');

        $currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('verifyPublishingTargetWorkspace'), array('live'));
        $currentWorkspace->expects($this->never())->method('verifyPublishingTargetWorkspace');

        $mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->disableOriginalConstructor()->getMock();

        $currentWorkspace->publishNode($mockNode, $targetWorkspace);
    }

    /**
     * Bug NEOS-1769: Content Collections disappear when publishing to other workspace than "live"
     *
     * Under certain circumstances, content collection nodes will be deleted when publishing a document to a workspace which is based on another workspace.
     *
     * @test
     */
    public function publishNodeReturnsIfTheTargetWorkspaceIsTheSameAsTheSourceWorkspace()
    {
        $liveWorkspace = new Workspace('live');
        $workspace = new Workspace('some-campaign');
        $workspace->setBaseWorkspace($liveWorkspace);

        $mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->any())->method('getWorkspace')->will($this->returnValue($workspace));

        $mockNode->expects($this->never())->method('emitBeforeNodePublishing');

        $workspace->publishNode($mockNode, $workspace);
    }

    /**
     * @test
     */
    public function verifyPublishingTargetWorkspaceDoesNotThrowAnExceptionIfTargetWorkspaceIsABaseWorkspace()
    {
        $someBaseWorkspace = new Workspace('live');
        $reviewWorkspace = new Workspace('review', $someBaseWorkspace);
        $currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array('user-foo', $reviewWorkspace));

        $currentWorkspace->_call('verifyPublishingTargetWorkspace', $reviewWorkspace);
        $currentWorkspace->_call('verifyPublishingTargetWorkspace', $someBaseWorkspace);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\WorkspaceException
     */
    public function verifyPublishingTargetWorkspaceThrowsAnExceptionIfWorkspaceIsNotBasedOnTheSpecifiedWorkspace()
    {
        $someBaseWorkspace = new Workspace('live');
        $currentWorkspace = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array('dummy'), array('user-foo', $someBaseWorkspace));
        $otherWorkspace = new Workspace('user-bar', $someBaseWorkspace);

        $currentWorkspace->_call('verifyPublishingTargetWorkspace', $otherWorkspace);
    }

    /**
     * @return array
     */
    public function validContextNodePaths()
    {
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
    public function contextNodePathMatchPatternMatchesNodeContextPaths($contextNodePath)
    {
        preg_match(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::MATCH_PATTERN_CONTEXTPATH, $contextNodePath, $matches);
        $this->assertArrayHasKey('WorkspaceName', $matches);
    }

    /**
     * @return array
     */
    public function invalidContextNodePaths()
    {
        return array(
            array('foo@user-bar.html'),
            array('foo/bar/baz')
        );
    }

    /**
     * @test
     * @dataProvider invalidContextNodePaths
     */
    public function contextNodePathMatchPatternDoesNotMatchInvalidNodeContextPaths($contextNodePath)
    {
        preg_match(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::MATCH_PATTERN_CONTEXTPATH, $contextNodePath, $matches);
        $this->assertArrayNotHasKey('WorkspaceName', $matches);
    }

    /**
     * @test
     */
    public function publishNodeWithANodeInTheTargetWorkspaceShouldDoNothing()
    {
        $liveWorkspace = new Workspace('live');
        $personalWorkspace = new Workspace('user-admin', $liveWorkspace);

        $nodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->getMock();
        $this->inject($liveWorkspace, 'nodeDataRepository', $nodeDataRepository);

        $node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $node->expects($this->any())->method('getWorkspace')->will($this->returnValue($liveWorkspace));

        $nodeDataRepository->expects($this->never())->method('findOneByIdentifier');

        $personalWorkspace->publishNode($node, $liveWorkspace);
    }

    /**
     * @test
     */
    public function isPersonalWorkspaceChecksIfTheWorkspaceNameStartsWithUser()
    {
        $liveWorkspace = new Workspace('live');
        $personalWorkspace = new Workspace('user-admin', $liveWorkspace);

        $this->assertFalse($liveWorkspace->isPersonalWorkspace());
        $this->assertTrue($personalWorkspace->isPersonalWorkspace());
    }
}
