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

use Neos\ContentRepository\Exception\WorkspaceException;
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
        self::assertSame('MyWorkspace', $workspace->getName());
        self::assertSame($baseWorkspace, $workspace->getBaseWorkspace());
    }

    /**
     * @test
     */
    public function onInitializationANewlyCreatedWorkspaceCreatesItsOwnRootNode()
    {
        $workspace = $this->getAccessibleMock(Workspace::class, ['dummy'], [], '', false);

        $mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(['add'])->getMock();
        $mockNodeDataRepository->expects(self::once())->method('add');

        $workspace->_set('nodeDataRepository', $mockNodeDataRepository);

        $workspace->initializeObject(ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);

        self::assertInstanceOf(NodeData::class, $workspace->getRootNodeData());
    }

    /**
     * @test
     */
    public function getNodeCountCallsRepositoryFunction()
    {
        $mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(['countByWorkspace'])->getMock();

        $workspace = $this->getAccessibleMock(Workspace::class, ['dummy'], [], '', false);
        $workspace->_set('nodeDataRepository', $mockNodeDataRepository);

        $mockNodeDataRepository->expects(self::once())->method('countByWorkspace')->with($workspace)->will(self::returnValue(42));

        self::assertSame(42, $workspace->getNodeCount());
    }

    /**
     * @test
     */
    public function publishNodeReturnsIfTheCurrentWorkspaceHasNoBaseWorkspace()
    {
        $targetWorkspace = new Workspace('live');

        $currentWorkspace = $this->getAccessibleMock(Workspace::class, ['verifyPublishingTargetWorkspace'], ['live']);
        $currentWorkspace->expects(self::never())->method('verifyPublishingTargetWorkspace');

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
        $workspace = $this->getMockBuilder(Workspace::class)->setMethods(['emitBeforeNodePublishing'])->setConstructorArgs(['some-campaign'])->getMock();
        $workspace->setBaseWorkspace($liveWorkspace);

        $mockNode = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects(self::any())->method('getWorkspace')->will(self::returnValue($workspace));

        $workspace->expects(self::never())->method('emitBeforeNodePublishing');

        $workspace->publishNode($mockNode, $workspace);
    }

    /**
     * @test
     */
    public function verifyPublishingTargetWorkspaceDoesNotThrowAnExceptionIfTargetWorkspaceIsABaseWorkspace()
    {
        $someBaseWorkspace = new Workspace('live');
        $reviewWorkspace = new Workspace('review', $someBaseWorkspace);
        $currentWorkspace = $this->getAccessibleMock(Workspace::class, ['dummy'], ['user-foo', $reviewWorkspace]);

        $currentWorkspace->_call('verifyPublishingTargetWorkspace', $reviewWorkspace);
        $currentWorkspace->_call('verifyPublishingTargetWorkspace', $someBaseWorkspace);
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function verifyPublishingTargetWorkspaceThrowsAnExceptionIfWorkspaceIsNotBasedOnTheSpecifiedWorkspace()
    {
        $this->expectException(WorkspaceException::class);
        $someBaseWorkspace = new Workspace('live');
        $currentWorkspace = $this->getAccessibleMock(Workspace::class, ['dummy'], ['user-foo', $someBaseWorkspace]);
        $otherWorkspace = new Workspace('user-bar', $someBaseWorkspace);

        $currentWorkspace->_call('verifyPublishingTargetWorkspace', $otherWorkspace);
    }

    /**
     * @return array
     */
    public function validContextNodePaths()
    {
        return [
            ['foo@user-bar'],
            ['foo/bar/baz@user-bar'],
            ['foo@user-UpperCamelCasedUser'],
            ['foo/bar/baz@user-UpperCamelCasedUser']
        ];
    }

    /**
     * @test
     * @dataProvider validContextNodePaths
     */
    public function contextNodePathMatchPatternMatchesNodeContextPaths($contextNodePath)
    {
        preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $contextNodePath, $matches);
        self::assertArrayHasKey('WorkspaceName', $matches);
    }

    /**
     * @return array
     */
    public function invalidContextNodePaths()
    {
        return [
            ['foo@user-bar.html'],
            ['foo/bar/baz']
        ];
    }

    /**
     * @test
     * @dataProvider invalidContextNodePaths
     */
    public function contextNodePathMatchPatternDoesNotMatchInvalidNodeContextPaths($contextNodePath)
    {
        preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $contextNodePath, $matches);
        self::assertArrayNotHasKey('WorkspaceName', $matches);
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
        $node->expects(self::any())->method('getWorkspace')->will(self::returnValue($liveWorkspace));

        $nodeDataRepository->expects(self::never())->method('findOneByIdentifier');

        $personalWorkspace->publishNode($node, $liveWorkspace);
    }

    /**
     * @test
     */
    public function isPersonalWorkspaceChecksIfTheWorkspaceNameStartsWithUser()
    {
        $liveWorkspace = new Workspace('live');
        $personalWorkspace = new Workspace('user-admin', $liveWorkspace);

        self::assertFalse($liveWorkspace->isPersonalWorkspace());
        self::assertTrue($personalWorkspace->isPersonalWorkspace());
    }
}
