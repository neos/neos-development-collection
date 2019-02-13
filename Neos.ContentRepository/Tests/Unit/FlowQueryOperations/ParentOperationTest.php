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
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Eel\FlowQueryOperations\ParentOperation;

/**
 * Testcase for the FlowQuery ParentsOperation
 */
class ParentOperationTest extends UnitTestCase
{
    /**
     * @var Context
     */
    protected $mockContext;

    /**
     * @var TraversableNodeInterface
     */
    protected $siteNode;

    /**
     * @var TraversableNodeInterface
     */
    protected $firstLevelNode;

    /**
     * @var TraversableNodeInterface
     */
    protected $secondLevelNode;

    public function setUp()
    {
        $this->siteNode = $this->createMock(TraversableNodeInterface::class);
        $this->firstLevelNode = $this->createMock(TraversableNodeInterface::class);
        $this->secondLevelNode = $this->createMock(TraversableNodeInterface::class);

        $this->siteNode->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/site')));
        $this->siteNode->expects($this->any())->method('findChildNodes')->will($this->returnValue([$this->firstLevelNode]));
        $this->siteNode->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(NodeAggregateIdentifier::fromString('site-identifier-uuid')));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->firstLevelNode->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->firstLevelNode->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/site/first')));
        $this->secondLevelNode->expects($this->any())->method('findParentNode')->will($this->returnValue($this->siteNode));
        $this->secondLevelNode->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/site/first/second')));
    }

    /**
     * @test
     */
    public function parentWillReturnEmptyResultForTheSiteNode()
    {
        $context = [$this->siteNode];
        $q = new FlowQuery($context);

        $operation = new ParentOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([], $output);
    }

    /**
     * @test
     */
    public function parentWillReturnFirstLevelNodeForSecondLevelNode()
    {
        $context = [$this->secondLevelNode];
        $q = new FlowQuery($context);

        $operation = new ParentOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        $this->assertEquals([$this->firstLevelNode], $output);
    }
}
