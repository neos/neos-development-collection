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

    public function setUp()
    {
        $this->siteNode = $this->createMock(NodeInterface::class);
        $this->firstNodeInLevel = $this->createMock(NodeInterface::class);
        $this->secondNodeInLevel = $this->createMock(NodeInterface::class);
        $this->thirdNodeInLevel = $this->createMock(NodeInterface::class);

        $this->siteNode->expects($this->any())->method('getPath')->will($this->returnValue('/site'));
        $this->siteNode->expects($this->any())->method('getChildNodes')->will($this->returnValue(array(
            $this->firstNodeInLevel,
            $this->secondNodeInLevel,
            $this->thirdNodeInLevel
        )));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->firstNodeInLevel->expects($this->any())->method('getParent')->will($this->returnValue($this->siteNode));
        $this->firstNodeInLevel->expects($this->any())->method('getPath')->will($this->returnValue('/site/first'));
        $this->secondNodeInLevel->expects($this->any())->method('getParent')->will($this->returnValue($this->siteNode));
        $this->secondNodeInLevel->expects($this->any())->method('getPath')->will($this->returnValue('/site/second'));
        $this->thirdNodeInLevel->expects($this->any())->method('getParent')->will($this->returnValue($this->siteNode));
        $this->thirdNodeInLevel->expects($this->any())->method('getPath')->will($this->returnValue('/site/third'));
    }

    /**
     * @test
     */
    public function siblingsWillReturnEmptyResultForAllNodesInLevel()
    {
        $context = array($this->firstNodeInLevel, $this->secondNodeInLevel, $this->thirdNodeInLevel);
        $q = new FlowQuery($context);

        $operation = new SiblingsOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array(), $output);
    }

    /**
     * @test
     */
    public function siblingsWillReturnFirstAndThirdNodeInLevelForSecondNodeInLevel()
    {
        $context = array($this->secondNodeInLevel);
        $q = new FlowQuery($context);

        $operation = new SiblingsOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array($this->firstNodeInLevel, $this->thirdNodeInLevel), $output);
    }

    /**
     * @test
     */
    public function siblingsWillReturnFirstNodeForSecondAndThirdNodeInLevel()
    {
        $context = array($this->secondNodeInLevel, $this->thirdNodeInLevel);
        $q = new FlowQuery($context);

        $operation = new SiblingsOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array($this->firstNodeInLevel), $output);
    }

    /**
     * @test
     */
    public function siblingsWillReturnEmptyArrayForSiteNode()
    {
        $context = array($this->siteNode);
        $q = new FlowQuery($context);

        $operation = new SiblingsOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array(), $output);
    }
}
