<?php
namespace TYPO3\TYPO3\Tests\Unit\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Content TypoScript object
 *
 */
class ContentTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function offsetGetInitializesContentOnFirstCall() {
		$content = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content->expects($this->once())->method('initializeSections');

		$content->offsetGet('foo');
	}

	/**
	 * @test
	 */
	public function offsetGetReturnsNullForNonExistantOffset() {
		$content = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Content', array('initializeSections'));

		$this->assertNull($content->offsetGet('foo'));
	}

	/**
	 * @test
	 */
	public function offsetExistsChecksIfSectionExists() {
		$mockTypoScriptObject = $this->getMock('TYPO3\TypoScript\ContentObjectInterface');

		$content = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content['foo'] = $mockTypoScriptObject;

		$this->assertTrue($content->offsetExists('foo'));
	}

	/**
	 * @test
	 */
	public function offsetSetInitializesContentOnFirstCall() {
		$content = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content->expects($this->once())->method('initializeSections');

		$content->offsetSet('foo', $this->getMock('TYPO3\TypoScript\ContentObjectInterface'));
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function offsetSetThrowsExceptionIfInvalidValueIsGiven() {
		$content = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content->expects($this->once())->method('initializeSections');

		$content->offsetSet('foo', 'bar');
	}

	/**
	 * @test
	 */
	public function usingArrayAccessASetValueCanBeRetrievedAgain() {
		$content = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Content', array('initializeSections'));
		$value = $this->getMock('TYPO3\TypoScript\ContentObjectInterface');

		$content['foo'] = $value;
		$this->assertSame($value, $content['foo']);
	}

	/**
	 * @test
	 */
	public function offsetUnsetInitializesContentOnFirstCall() {
		$content = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content->expects($this->once())->method('initializeSections');

		$content->offsetUnset('foo');
	}

	/**
	 * @test
	 */
	public function offsetUnsetWorks() {
		$content = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content['foo'] = $this->getMock('TYPO3\TypoScript\ContentObjectInterface');

		$content->offsetUnset('foo');
		$this->assertNull($content['foo']);
	}

}
?>