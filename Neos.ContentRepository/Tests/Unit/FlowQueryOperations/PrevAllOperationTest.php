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
use Neos\ContentRepository\Eel\FlowQueryOperations\PrevAllOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the FlowQuery PrevAllOperation
 */
class PrevAllOperationTest extends AbstractQueryOperationsTest
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

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->siteNode = $this->mockNode('site');
        $this->firstNodeInLevel = $this->mockNode('node1');
        $this->secondNodeInLevel = $this->mockNode('node2');
        $this->thirdNodeInLevel = $this->mockNode('node3');

        $this->siteNode->expects(self::any())->method('findNodePath')->will(self::returnValue(NodePath::fromString('/site')));
        $this->siteNode->expects(self::any())->method('findChildNodes')->will(self::returnValue(TraversableNodes::fromArray([
            $this->firstNodeInLevel,
            $this->secondNodeInLevel,
            $this->thirdNodeInLevel
        ])));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->firstNodeInLevel->expects(self::any())->method('findParentNode')->will(self::returnValue($this->siteNode));
        $this->secondNodeInLevel->expects(self::any())->method('findParentNode')->will(self::returnValue($this->siteNode));
        $this->thirdNodeInLevel->expects(self::any())->method('findParentNode')->will(self::returnValue($this->siteNode));
    }

    /**
     * @test
     */
    public function prevAllWillReturnEmptyResultForFirstNodeInLevel()
    {
        $context = [$this->firstNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevAllOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([], $output);
    }

    /**
     * @test
     */
    public function prevAllWillReturnFirstNodeInLevelForSecondNodeInLevel()
    {
        $context = [$this->secondNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevAllOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([$this->firstNodeInLevel], $output);
    }

    /**
     * @test
     */
    public function prevAllWillReturnFirstNodeAndSecondNodeInLevelForSecondAndThirdNodeInLevel()
    {
        $context = [$this->secondNodeInLevel, $this->thirdNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevAllOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([$this->firstNodeInLevel, $this->secondNodeInLevel], $output);
    }

    /**
     * @test
     */
    public function prevAllWillReturnFirstNodeAndSecondNodeInLevelForThirdNodeInLevel()
    {
        $context = [$this->thirdNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevAllOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([$this->firstNodeInLevel, $this->secondNodeInLevel], $output);
    }
}
