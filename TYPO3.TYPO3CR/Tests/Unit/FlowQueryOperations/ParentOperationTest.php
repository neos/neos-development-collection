<?php
namespace TYPO3\TYPO3CR\Tests\Unit\FlowQueryOperations;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Eel\FlowQueryOperations\ParentOperation;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

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
     * @var NodeInterface
     */
    protected $siteNode;

    /**
     * @var NodeInterface
     */
    protected $firstLevelNode;

    /**
     * @var NodeInterface
     */
    protected $secondLevelNode;

    public function setUp()
    {
        $this->siteNode = $this->createMock(NodeInterface::class);
        $this->firstLevelNode = $this->createMock(NodeInterface::class);
        $this->secondLevelNode = $this->createMock(NodeInterface::class);

        $this->siteNode->expects($this->any())->method('getPath')->will($this->returnValue('/site'));
        $this->siteNode->expects($this->any())->method('getChildNodes')->will($this->returnValue(array($this->firstLevelNode)));
        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->firstLevelNode->expects($this->any())->method('getParent')->will($this->returnValue($this->siteNode));
        $this->firstLevelNode->expects($this->any())->method('getPath')->will($this->returnValue('/site/first'));
        $this->secondLevelNode->expects($this->any())->method('getParent')->will($this->returnValue($this->siteNode));
        $this->secondLevelNode->expects($this->any())->method('getPath')->will($this->returnValue('/site/first/second'));
    }

    /**
     * @test
     */
    public function parentWillReturnEmptyResultForTheSiteNode()
    {
        $context = array($this->siteNode);
        $q = new FlowQuery($context);

        $operation = new ParentOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array(), $output);
    }

    /**
     * @test
     */
    public function parentWillReturnFirstLevelNodeForSecondLevelNode()
    {
        $context = array($this->secondLevelNode);
        $q = new FlowQuery($context);

        $operation = new ParentOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array($this->firstLevelNode), $output);
    }
}
