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
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodes;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Eel\FlowQueryOperations\PrevOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the FlowQuery PrevOperation
 */
class PrevOperationTest extends AbstractQueryOperationsTestCase
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

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->markTestSkipped('fix and re-enable for Neos 9.0');

        $this->siteNode = $this->mockNode('site-node');
        $this->firstNodeInLevel = $this->mockNode('first-node');
        $this->secondNodeInLevel = $this->mockNode('second-node');
        $this->thirdNodeInLevel = $this->mockNode('third-node');

        $this->siteNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/site'));
        $this->siteNode->expects(self::any())->method('findChildNodes')->willReturn(TraversableNodes::fromArray([
            $this->firstNodeInLevel,
            $this->secondNodeInLevel,
            $this->thirdNodeInLevel
        ]));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->firstNodeInLevel->expects(self::any())->method('findParentNode')->willReturn($this->siteNode);
        $this->secondNodeInLevel->expects(self::any())->method('findParentNode')->willReturn($this->siteNode);
        $this->thirdNodeInLevel->expects(self::any())->method('findParentNode')->willReturn($this->siteNode);
    }

    #[Test]
    public function prevWillReturnEmptyResultForFirstNodeInLevel()
    {
        $context = [$this->firstNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([], $output);
    }

    #[Test]
    public function prevWillReturnFirstNodeInLevelForSecondNodeInLevel()
    {
        $context = [$this->secondNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([$this->firstNodeInLevel], $output);
    }

    #[Test]
    public function prevWillReturnFirstNodeAndSecondNodeInLevelForSecondAndThirdNodeInLevel()
    {
        $context = [$this->secondNodeInLevel, $this->thirdNodeInLevel];
        $q = new FlowQuery($context);

        $operation = new PrevOperation();
        $operation->evaluate($q, []);

        $output = $q->getContext();
        self::assertEquals([$this->firstNodeInLevel, $this->secondNodeInLevel], $output);
    }
}
