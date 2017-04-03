<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;

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
        $workspace = $this->getAccessibleMock(Workspace::class, array('dummy'), array(), '', false);

        $mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(array('add'))->getMock();
        $mockNodeDataRepository->expects($this->once())->method('add');

        $workspace->_set('nodeDataRepository', $mockNodeDataRepository);

        $workspace->initializeObject(ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);

        $this->assertInstanceOf(NodeData::class, $workspace->getRootNodeData());
    }

    /**
     * @test
     */
    public function getNodeCountCallsRepositoryFunction()
    {
        $mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(array('countByWorkspace'))->getMock();

        $workspace = $this->getAccessibleMock(Workspace::class, array('dummy'), array(), '', false);
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

        $currentWorkspace = $this->getAccessibleMock(Workspace::class, array('verifyPublishingTargetWorkspace'), array('live'));
        $currentWorkspace->expects($this->never())->method('verifyPublishingTargetWorkspace');

        $mockNode = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();

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
        $workspace = $this->getMockBuilder(Workspace::class)->setMethods(array('emitBeforeNodePublishing'))->setConstructorArgs(array('some-campaign'))->getMock();
        $workspace->setBaseWorkspace($liveWorkspace);

        $mockNode = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->any())->method('getWorkspace')->will($this->returnValue($workspace));

        $workspace->expects($this->never())->method('emitBeforeNodePublishing');

        $workspace->publishNode($mockNode, $workspace);
    }

    /**
     * @test
     */
    public function verifyPublishingTargetWorkspaceDoesNotThrowAnExceptionIfTargetWorkspaceIsABaseWorkspace()
    {
        $someBaseWorkspace = new Workspace('live');
        $reviewWorkspace = new Workspace('review', $someBaseWorkspace);
        $currentWorkspace = $this->getAccessibleMock(Workspace::class, array('dummy'), array('user-foo', $reviewWorkspace));

        $currentWorkspace->_call('verifyPublishingTargetWorkspace', $reviewWorkspace);
        $currentWorkspace->_call('verifyPublishingTargetWorkspace', $someBaseWorkspace);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\WorkspaceException
     */
    public function verifyPublishingTargetWorkspaceThrowsAnExceptionIfWorkspaceIsNotBasedOnTheSpecifiedWorkspace()
    {
        $someBaseWorkspace = new Workspace('live');
        $currentWorkspace = $this->getAccessibleMock(Workspace::class, array('dummy'), array('user-foo', $someBaseWorkspace));
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
        preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $contextNodePath, $matches);
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
        preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $contextNodePath, $matches);
        $this->assertArrayNotHasKey('WorkspaceName', $matches);
    }

    /**
     * @test
     */
    public function publishNodeWithANodeInTheTargetWorkspaceShouldDoNothing()
    {
        $liveWorkspace = new Workspace('live');
        $personalWorkspace = new Workspace('user-admin', $liveWorkspace);

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->getMock();
        $this->inject($liveWorkspace, 'nodeDataRepository', $nodeDataRepository);

        $node = $this->createMock(NodeInterface::class);
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
