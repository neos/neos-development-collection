<?php
namespace TYPO3\TYPO3CR\Tests\Unit\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the FlowQuery ParentsOperation
 */
class ParentsOperationTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function parentsWillReturnTheSiteNodeAsRootLevelParent() {
		$siteNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$firstLevelNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$secondLevelNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$siteNode->expects($this->any())->method('getPath')->will($this->returnValue('/site'));
		$mockContext = $this->getMockBuilder('TYPO3\Neos\Domain\Service\ContentContext')->disableOriginalConstructor()->getMock();
		$mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($siteNode));
		$firstLevelNode->expects($this->any())->method('getParent')->will($this->returnValue($siteNode));
		$firstLevelNode->expects($this->any())->method('getPath')->will($this->returnValue('/site/first'));
		$secondLevelNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));
		$secondLevelNode->expects($this->any())->method('getParent')->will($this->returnValue($firstLevelNode));
		$secondLevelNode->expects($this->any())->method('getPath')->will($this->returnValue('/site/first/second'));

		$context = array($secondLevelNode);
		$q = new \TYPO3\Eel\FlowQuery\FlowQuery($context);

		$operation = new \TYPO3\TYPO3CR\Eel\FlowQueryOperations\ParentsOperation();
		$operation->evaluate($q, array());

		$output = $q->getContext();
		$this->assertEquals(array($siteNode, $firstLevelNode), $output);
	}

}
