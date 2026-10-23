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
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Eel\FlowQueryOperations\ParentOperation;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the FlowQuery ParentsOperation
 */
class ParentOperationTest extends AbstractQueryOperationsTestCase
{
    /**
     * @var Context
     */
    protected $mockContext;

    /**
     * @var TraversableNode
     */
    protected $siteNode;

    /**
     * @var TraversableNode
     */
    protected $firstLevelNode;

    /**
     * @var TraversableNode
     */
    protected $secondLevelNode;

    public function setUp(): void
    {
        $this->markTestSkipped('fix and re-enable for Neos 9.0');

        $this->siteNode = $this->mockNode('site-identifier-uuid');
        $this->firstLevelNode = $this->mockNode('node1');
        $this->secondLevelNode = $this->mockNode('node2');

        $this->siteNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/site'));
        $this->siteNode->expects(self::any())->method('findChildNodes')->willReturn(TraversableNodes::fromArray([$this->firstLevelNode]));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->siteNode->expects(self::any())->method('findParentNode')->willThrowException(new NodeException('No parent'));
        $this->firstLevelNode->expects(self::any())->method('findParentNode')->willReturn($this->siteNode);
        $this->firstLevelNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/site/first'));
        $this->secondLevelNode->expects(self::any())->method('findParentNode')->willReturn($this->siteNode);
        $this->secondLevelNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/site/first/second'));
    }

    #[Test]
    public function parentWillReturnEmptyResultForTheSiteNode()
    {
        $context = [$this->siteNode];
        $q = new FlowQuery($context);

        $operation = new ParentOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([], $output);
    }

    #[Test]
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
