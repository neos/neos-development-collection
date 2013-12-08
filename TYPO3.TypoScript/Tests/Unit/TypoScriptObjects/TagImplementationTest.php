<?php
namespace TYPO3\TypoScript\Tests\Unit\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\TypoScriptObjects\TagImplementation;

/**
 * Testcase for the TypoScript Tag object
 */
class TagImplementationTest extends UnitTestCase {

	/**
	 * @var Runtime
	 */
	protected $mockTsRuntime;

	public function setUp() {
		parent::setUp();
		$this->mockTsRuntime = $this->getMockBuilder('TYPO3\TypoScript\Core\Runtime')->disableOriginalConstructor()->getMock();
	}

	public function tagExamples() {
		return array(
			'default properties' => array(array(), NULL, NULL, '<div></div>'),
			'omit closing tag' => array(array('omitClosingTag' => TRUE), NULL, NULL, '<div>'),
			'force self closing tag' => array(array('selfClosingTag' => TRUE), NULL, NULL, '<div />'),
			'auto self closing tag' => array(array('tagName' => 'input'), ' type="text"', NULL, '<input type="text" />'),
			'tag name with content' => array(array('tagName' => 'h1'), NULL, 'Foo', '<h1>Foo</h1>'),
			'tag with attribute' => array(array('tagName' => 'link'), ' type="text/css" rel="stylesheet"', NULL, '<link type="text/css" rel="stylesheet" />'),
			'tag with array of classes' => array(array('tagName' => 'div'), ' class="icon icon-neos"', NULL, '<div class="icon icon-neos"></div>')
		);
	}

	/**
	 * @test
	 * @dataProvider tagExamples
	 */
	public function evaluateTests($properties, $attributes, $content, $expectedOutput) {
		$path = 'tag/test';
		$this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function($evaluatePath, $that) use ($path, $attributes, $content) {
			$relativePath = str_replace($path . '/', '', $evaluatePath);
			switch ($relativePath) {
				case 'attributes':
					return $attributes;
				case 'content':
					return $content;
			}
			return ObjectAccess::getProperty($that, $relativePath, TRUE);
		}));

		$typoScriptObjectName = 'TYPO3.TypoScript:Tag';
		$renderer = new TagImplementation($this->mockTsRuntime, $path, $typoScriptObjectName);

		foreach ($properties as $name => $value) {
			ObjectAccess::setProperty($renderer, $name, $value);
		}

		$result = $renderer->evaluate();
		$this->assertEquals($expectedOutput, $result);
	}

}
