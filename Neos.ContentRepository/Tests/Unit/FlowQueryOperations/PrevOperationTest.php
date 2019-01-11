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

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodes;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Eel\FlowQueryOperations\PrevOperation;

/**
 * Testcase for the FlowQuery PrevOperation
 */
class PrevOperationTest extends UnitTestCase
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
    public function setUp()
    {
        $this->siteNode = $this->createMock(TraversableNodeInterface::class);
        $this->firstNodeInLevel = $this->createMock(TraversableNodeInterface::class);
        $this->secondNodeInLevel = $this->createMock(TraversableNodeInterface::class);
        $this->thirdNodeInLevel = $this->createMock(TraversableNodeInterface::class);

        $this->siteNode->expects($this->any())->method('findNodePath')->will($this->returnValue(new NodePath('/site')));
        $this->siteNode->expects($this->any())->method('findChildNodes')->will($this->returnValue(TraversableNodes::fromArray([
            $this->firstNodeInLevel,
            $this->secondNodeInLevel,
            $this->thirdNodeInLevel
        ])));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->firstNodeInLevel->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->firstNodeInLevel->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(new NodeAggregateIdentifier('first-node')));
        $this->secondNodeInLevel->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->secondNodeInLevel->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(new NodeAggregateIdentifier('second-node')));
        $this->thirdNodeInLevel->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->thirdNodeInLevel->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(new NodeAggregateIdentifier('third-node')));
    }

    /**
     * @test
     */
    public function prevWillReturnEmptyResultForFirstNodeInLevel()
    {
        $context = [$this->firstNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([], $output);
    }

    /**
     * @test
     */
    public function prevWillReturnFirstNodeInLevelForSecondNodeInLevel()
    {
        $context = [$this->secondNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([$this->firstNodeInLevel], $output);
    }

    /**
     * @test
     */
    public function prevWillReturnFirstNodeAndSecondNodeInLevelForSecondAndThirdNodeInLevel()
    {
        $context = [$this->secondNodeInLevel, $this->thirdNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([$this->firstNodeInLevel, $this->secondNodeInLevel], $output);
    }
}
