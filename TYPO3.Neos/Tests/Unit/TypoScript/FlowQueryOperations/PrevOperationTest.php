<?php
namespace TYPO3\Neos\Tests\Unit\TypoScript\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Testcase for the Neos FlowQuery PrevOperation
 */
class PrevOperationTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var ContentContext
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

	public function setUp() {
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
		$this->mockContext = $this->getMockBuilder('TYPO3\Neos\Domain\Service\ContentContext')->disableOriginalConstructor()->getMock();
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
	public function prevWillReturnEmptyResultForFirstNodeInLevel() {
		$context = array($this->firstNodeInLevel);
		$q = new \TYPO3\Eel\FlowQuery\FlowQuery($context);

		$operation = new \TYPO3\Neos\TypoScript\FlowQueryOperations\PrevOperation();
		$operation->evaluate($q, array());

		$output = $q->getContext();
		$this->assertEquals(array(), $output);
	}

	/**
	 * @test
	 */
	public function prevWillReturnFirstNodeInLevelForSecondNodeInLevel() {
		$context = array($this->secondNodeInLevel);
		$q = new \TYPO3\Eel\FlowQuery\FlowQuery($context);

		$operation = new \TYPO3\Neos\TypoScript\FlowQueryOperations\PrevOperation();
		$operation->evaluate($q, array());

		$output = $q->getContext();
		$this->assertEquals(array($this->firstNodeInLevel), $output);
	}

	/**
	 * @test
	 */
	public function prevWillReturnFirstNodeAndSecondNodeInLevelForSecondAndThirdNodeInLevel() {
		$context = array($this->secondNodeInLevel, $this->thirdNodeInLevel);
		$q = new \TYPO3\Eel\FlowQuery\FlowQuery($context);

		$operation = new \TYPO3\Neos\TypoScript\FlowQueryOperations\PrevOperation();
		$operation->evaluate($q, array());

		$output = $q->getContext();
		$this->assertEquals(array($this->firstNodeInLevel, $this->secondNodeInLevel), $output);
	}

}
