<?php

namespace Neos\ContentRepository\NodeAccess\Tests\Unit\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodes;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Eel\FlowQueryOperations\NextAllOperation;
use Neos\Eel\FlowQuery\FlowQuery;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the FlowQuery NextAllOperation
 */
class NextAllOperationTest extends AbstractQueryOperationsTestCase
{
    /**
     * @var Context
     */
    protected $mockContext;

    /**
     * @var Node
     */
    protected $siteNode;

    /**
     * @var Node
     */
    protected $firstNodeInLevel;

    /**
     * @var Node
     */
    protected $secondNodeInLevel;

    /**
     * @var Node
     */
    protected $thirdNodeInLevel;

    public function setUp(): void
    {
        $this->markTestSkipped('fix and re-enable for Neos 9.0');

        $this->siteNode = $this->mockNode('site');
        $this->firstNodeInLevel = $this->mockNode('node1');
        $this->secondNodeInLevel = $this->mockNode('node2');
        $this->thirdNodeInLevel = $this->mockNode('node3');

        $this->siteNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/site'));
        $this->siteNode->expects(self::any())->method('findChildNodes')->willReturn(TraversableNodes::fromArray([
            $this->firstNodeInLevel,
            $this->secondNodeInLevel,
            $this->thirdNodeInLevel
        ]));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->firstNodeInLevel->expects(self::any())->method('findParentNode')->willReturn($this->siteNode);
        $this->firstNodeInLevel->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/site/first'));
        $this->secondNodeInLevel->expects(self::any())->method('findParentNode')->willReturn($this->siteNode);
        $this->secondNodeInLevel->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/site/second'));
        $this->thirdNodeInLevel->expects(self::any())->method('findParentNode')->willReturn($this->siteNode);
        $this->thirdNodeInLevel->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/site/third'));
    }

    #[Test]
    public function nextAllWillReturnEmptyResultForLastNodeInLevel()
    {
        $context = [$this->thirdNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new NextAllOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([], $output);
    }

    #[Test]
    public function nextAllWillReturnSecondNodeAndThirdNodeInLevelForFirstNodeInLevel()
    {
        $context = [$this->firstNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new NextAllOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([$this->secondNodeInLevel, $this->thirdNodeInLevel], $output);
    }

    #[Test]
    public function nextAllWillReturnThirdNodeInLevelForSecondNodeInLevel()
    {
        $context = [$this->secondNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new NextAllOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([$this->thirdNodeInLevel], $output);
    }

    #[Test]
    public function nextAllWillReturnSecondNodeAndThirdNodeInLevelForFirstAndSecondNodeInLevel()
    {
        $context = [$this->firstNodeInLevel, $this->secondNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new NextAllOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([$this->secondNodeInLevel, $this->thirdNodeInLevel], $output);
    }
}
