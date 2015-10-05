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
use TYPO3\TYPO3CR\Eel\FlowQueryOperations\ParentOperation;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Testcase for the FlowQuery ParentsOperation
 */
class ParentOperationTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\Context
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
        $this->siteNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $this->firstLevelNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $this->secondLevelNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

        $this->siteNode->expects($this->any())->method('getPath')->will($this->returnValue('/site'));
        $this->siteNode->expects($this->any())->method('getChildNodes')->will($this->returnValue(array($this->firstLevelNode)));
        $this->mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $this->mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($this->siteNode));

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
