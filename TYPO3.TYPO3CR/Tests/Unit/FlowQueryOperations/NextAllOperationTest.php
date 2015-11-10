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

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Testcase for the FlowQuery NextAllOperation
 */
class NextAllOperationTest extends \TYPO3\Flow\Tests\UnitTestCase
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
        $this->siteNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $this->firstNodeInLevel = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $this->secondNodeInLevel = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $this->thirdNodeInLevel = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

        $this->siteNode->expects($this->any())->method('getPath')->will($this->returnValue('/site'));
        $this->siteNode->expects($this->any())->method('getChildNodes')->will($this->returnValue(array(
            $this->firstNodeInLevel,
            $this->secondNodeInLevel,
            $this->thirdNodeInLevel
        )));
        $this->mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $this->mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($this->siteNode));

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
    public function nextAllWillReturnEmptyResultForLastNodeInLevel()
    {
        $context = array($this->thirdNodeInLevel);
        $q = new \TYPO3\Eel\FlowQuery\FlowQuery($context);

        $operation = new \TYPO3\TYPO3CR\Eel\FlowQueryOperations\NextAllOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array(), $output);
    }

    /**
     * @test
     */
    public function nextAllWillReturnSecondNodeAndThirdNodeInLevelForFirstNodeInLevel()
    {
        $context = array($this->firstNodeInLevel);
        $q = new \TYPO3\Eel\FlowQuery\FlowQuery($context);

        $operation = new \TYPO3\TYPO3CR\Eel\FlowQueryOperations\NextAllOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array($this->secondNodeInLevel, $this->thirdNodeInLevel), $output);
    }

    /**
     * @test
     */
    public function nextAllWillReturnThirdNodeInLevelForSecondNodeInLevel()
    {
        $context = array($this->secondNodeInLevel);
        $q = new \TYPO3\Eel\FlowQuery\FlowQuery($context);

        $operation = new \TYPO3\TYPO3CR\Eel\FlowQueryOperations\NextAllOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array($this->thirdNodeInLevel), $output);
    }

    /**
     * @test
     */
    public function nextAllWillReturnSecondNodeAndThirdNodeInLevelForFirstAndSecondNodeInLevel()
    {
        $context = array($this->firstNodeInLevel, $this->secondNodeInLevel);
        $q = new \TYPO3\Eel\FlowQuery\FlowQuery($context);

        $operation = new \TYPO3\TYPO3CR\Eel\FlowQueryOperations\NextAllOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array($this->secondNodeInLevel, $this->thirdNodeInLevel), $output);
    }
}
