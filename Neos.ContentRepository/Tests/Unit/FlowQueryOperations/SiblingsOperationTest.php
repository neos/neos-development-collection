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
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Eel\FlowQueryOperations\SiblingsOperation;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * Testcase for the FlowQuery SiblingsOperation
 */
class SiblingsOperationTest extends UnitTestCase
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

        $this->siteNode->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(NodeAggregateIdentifier::fromString('site')));
        $this->siteNode->expects($this->any())->method('findChildNodes')->will($this->returnValue(TraversableNodes::fromArray([
            $this->firstNodeInLevel,
            $this->secondNodeInLevel,
            $this->thirdNodeInLevel
        ])));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->siteNode->expects($this->any())->method('findParentNode')->will($this->throwException(new NodeException('No parent')));
        $this->firstNodeInLevel->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->firstNodeInLevel->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(NodeAggregateIdentifier::fromString('first-node')));
        $this->secondNodeInLevel->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->secondNodeInLevel->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(NodeAggregateIdentifier::fromString('second-node')));
        $this->thirdNodeInLevel->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->thirdNodeInLevel->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(NodeAggregateIdentifier::fromString('third-node')));
    }

    /**
     * @test
     */
    public function siblingsWillReturnEmptyResultForAllNodesInLevel()
    {
        $context = [$this->firstNodeInLevel, $this->secondNodeInLevel, $this->thirdNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new SiblingsOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([], $output);
    }

    /**
     * @test
     */
    public function siblingsWillReturnFirstAndThirdNodeInLevelForSecondNodeInLevel()
    {
        $context = [$this->secondNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new SiblingsOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([$this->firstNodeInLevel, $this->thirdNodeInLevel], $output);
    }

    /**
     * @test
     */
    public function siblingsWillReturnFirstNodeForSecondAndThirdNodeInLevel()
    {
        $context = [$this->secondNodeInLevel, $this->thirdNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new SiblingsOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([$this->firstNodeInLevel], $output);
    }

    /**
     * @test
     */
    public function siblingsWillReturnEmptyArrayForSiteNode()
    {
        $context = [$this->siteNode];
        $q = new FlowQuery($context);

        $operation = new SiblingsOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([], $output);
    }
}
