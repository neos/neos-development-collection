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
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Eel\FlowQueryOperations\ParentOperation;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the FlowQuery ParentsOperation
 */
class ParentOperationTest extends AbstractQueryOperationsTest
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

    public function setUp(): void
    {
        $this->siteNode = $this->mockNode('site-identifier-uuid');
        $this->firstLevelNode = $this->mockNode('node1');
        $this->secondLevelNode = $this->mockNode('node2');

        $this->siteNode->expects(self::any())->method('findNodePath')->will(self::returnValue(NodePath::fromString('/site')));
        $this->siteNode->expects(self::any())->method('findChildNodes')->will(self::returnValue([$this->firstLevelNode]));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->siteNode->expects(self::any())->method('findParentNode')->will(self::throwException(new NodeException('No parent')));
        $this->firstLevelNode->expects(self::any())->method('findParentNode')->will(self::returnValue($this->siteNode));
        $this->firstLevelNode->expects(self::any())->method('findNodePath')->will(self::returnValue(NodePath::fromString('/site/first')));
        $this->secondLevelNode->expects(self::any())->method('findParentNode')->will(self::returnValue($this->siteNode));
        $this->secondLevelNode->expects(self::any())->method('findNodePath')->will(self::returnValue(NodePath::fromString('/site/first/second')));
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
        self::assertEquals([], $output);
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
        self::assertEquals([$this->firstLevelNode], $output);
    }
}
