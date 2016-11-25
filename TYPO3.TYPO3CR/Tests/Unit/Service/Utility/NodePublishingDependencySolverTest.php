<?php
namespace Neos\ContentRepository\Tests\Unit\Service\Utility;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Service\Utility\NodePublishingDependencySolver;

class NodePublishingDependencySolverTest extends UnitTestCase
{
    /**
     * @var Workspace
     */
    protected $mockWorkspace;

    /**
     * @var Context
     */
    protected $mockContext;

    public function setUp()
    {
        $this->mockWorkspace = $this->getMockBuilder(Workspace::class)->setConstructorArgs(array('live'))->getMock();
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function sortNodesWithParentRelations()
    {
        $nodeService = $this->buildNodeMock('/sites/typo3cr/service');
        $nodeCompany = $this->buildNodeMock('/sites/typo3cr/company');
        $nodeAboutUs = $this->buildNodeMock('/sites/typo3cr/company/about-us');

        $unpublishedNodes = array($nodeAboutUs, $nodeService, $nodeCompany);

        $solver = new NodePublishingDependencySolver();
        $sortedNodes = $solver->sort($unpublishedNodes);

        $this->assertBeforeInArray($nodeCompany, $nodeAboutUs, $sortedNodes);
    }

    /**
     * @test
     */
    public function sortNodesWithMovedToRelations()
    {
        $nodeEnterprise = $this->buildNodeMock('/sites/typo3cr/enterprise');

        // "company" was moved to "enterprise"
        $nodeCompany = $this->buildNodeMock('/sites/typo3cr/company', $nodeEnterprise->getNodeData());
        $nodeAboutUs = $this->buildNodeMock('/sites/typo3cr/company/about-us');

        // "service" was moved to "company"
        $nodeService = $this->buildNodeMock('/sites/typo3cr/service', $nodeCompany->getNodeData());

        $unpublishedNodes = array($nodeAboutUs, $nodeService, $nodeCompany, $nodeEnterprise);

        $solver = new NodePublishingDependencySolver();
        $sortedNodes = $solver->sort($unpublishedNodes);

        $this->assertBeforeInArray($nodeEnterprise, $nodeCompany, $sortedNodes);
        $this->assertBeforeInArray($nodeCompany, $nodeAboutUs, $sortedNodes);
        $this->assertBeforeInArray($nodeCompany, $nodeService, $sortedNodes);
    }

    /**
     * Build a mock Node for testing
     *
     * @param string $path
     * @param NodeData $movedTo
     * @return Node
     */
    protected function buildNodeMock($path, $movedTo = null)
    {
        $mockNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs(array($path, $this->mockWorkspace))->getMock();
        $mockNodeData->expects($this->any())->method('getMovedTo')->will($this->returnValue($movedTo));
        $mockNodeData->expects($this->any())->method('getPath')->will($this->returnValue($path));
        $mockNode = $this->getMockBuilder(Node::class)->setConstructorArgs(array($mockNodeData, $this->mockContext))->getMock();
        $mockNode->expects($this->any())->method('getNodeData')->will($this->returnValue($mockNodeData));
        $mockNode->expects($this->any())->method('getPath')->will($this->returnValue($path));
        $parentPath = substr($path, 0, strrpos($path, '/'));
        $mockNode->expects($this->any())->method('getParentPath')->will($this->returnValue($parentPath));

        return $mockNode;
    }

    /**
     * Assert the element1 is before element2 in the given list of elements
     *
     * @param mixed $element1
     * @param mixed $element2
     * @param array $elements
     * @return void
     */
    protected function assertBeforeInArray($element1, $element2, array $elements)
    {
        $position1 = array_search($element1, $elements, true);
        $position2 = array_search($element2, $elements, true);
        if ($position1 === false || $position2 === false) {
            $this->fail('Element not found in list');
        }
        $this->assertLessThan($position2, $position1, 'Element order does not match');
    }
}
