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

    public function setUp(): void
    {
        $this->mockWorkspace = $this->getMockBuilder(Workspace::class)->setConstructorArgs(['live'])->getMock();
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

        $unpublishedNodes = [$nodeAboutUs, $nodeService, $nodeCompany];

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

        $unpublishedNodes = [$nodeAboutUs, $nodeService, $nodeCompany, $nodeEnterprise];

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
        $mockNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs([$path, $this->mockWorkspace])->getMock();
        $mockNodeData->expects(self::any())->method('getMovedTo')->will(self::returnValue($movedTo));
        $mockNodeData->expects(self::any())->method('getPath')->will(self::returnValue($path));
        $mockNode = $this->getMockBuilder(Node::class)->setConstructorArgs([$mockNodeData, $this->mockContext])->getMock();
        $mockNode->expects(self::any())->method('getNodeData')->will(self::returnValue($mockNodeData));
        $mockNode->expects(self::any())->method('getPath')->will(self::returnValue($path));
        $parentPath = substr($path, 0, strrpos($path, '/'));
        $mockNode->expects(self::any())->method('getParentPath')->will(self::returnValue($parentPath));

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
        self::assertLessThan($position2, $position1, 'Element order does not match');
    }
}
