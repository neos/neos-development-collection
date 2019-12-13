<?php
namespace Neos\ContentRepository\Tests\Unit\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodes;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Eel\FlowQueryOperations\NextOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the FlowQuery NextOperation
 */
class NextOperationTest extends AbstractQueryOperationsTest
{
    /**
     * @var Context
     */
    protected $mockContext;

    /**
     * @var NodeInterface
     */
    protected $siteNode;

    /**
     * @var NodeInterface
     */
    protected $firstNodeInLevel;

    /**
     * @var NodeInterface
     */
    protected $secondNodeInLevel;

    /**
     * @var NodeInterface
     */
    protected $thirdNodeInLevel;

    public function setUp()
    {
        $this->siteNode = $this->mockNode('site');
        $this->firstNodeInLevel = $this->mockNode('node1');
        $this->secondNodeInLevel = $this->mockNode('node2');
        $this->thirdNodeInLevel = $this->mockNode('node3');

        $this->siteNode->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/site')));
        $this->siteNode->expects($this->any())->method('findChildNodes')->will($this->returnValue(TraversableNodes::fromArray([
            $this->firstNodeInLevel,
            $this->secondNodeInLevel,
            $this->thirdNodeInLevel
        ])));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->firstNodeInLevel->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->firstNodeInLevel->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/site/first')));
        $this->secondNodeInLevel->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->secondNodeInLevel->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/site/second')));
        $this->thirdNodeInLevel->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->thirdNodeInLevel->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/site/third')));
    }

    /**
     * @test
     */
    public function nextWillReturnEmptyResultForLastNodeInLevel()
    {
        $context = [$this->thirdNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new NextOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([], $output);
    }

    /**
     * @test
     */
    public function nextWillReturnSecondNodeInLevelForFirstNodeInLevel()
    {
        $context = [$this->firstNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new NextOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([$this->secondNodeInLevel], $output);
    }

    /**
     * @test
     */
    public function nextWillReturnSecondNodeAndThirdNodeInLevelForFirstAndSecondNodeInLevel()
    {
        $context = [$this->firstNodeInLevel, $this->secondNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new NextOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([$this->secondNodeInLevel, $this->thirdNodeInLevel], $output);
    }
}
