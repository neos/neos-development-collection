<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript View
 *
 */
class SimpleTypesTest extends AbstractTypoScriptObjectTest {

	/**
	 * @test
	 */
	public function stringSimpleTypeWorks() {
		$view = $this->buildView();
		$view->setTypoScriptPath('simpleTypes/string');
		$this->assertSame('A simple string value is not a TypoScript object', $view->render());
	}

	/**
	 * @test
	 */
	public function booleanSimpleTypeWorks() {
		$view = $this->buildView();
		$view->setTypoScriptPath('simpleTypes/booleanFalse');
		$this->assertSame(FALSE, $view->render());
		$view->setTypoScriptPath('simpleTypes/booleanTrue');
		$this->assertSame(TRUE, $view->render());
	}

	/**
	 * @test
	 */
	public function processorOnSimpleTypeWorks() {
		$view = $this->buildView();
		$view->setTypoScriptPath('simpleTypes/wrappedString');
		$this->assertSame('Hello, Foo', $view->render());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception\MissingTypoScriptObjectException
	 */
	public function renderingNonObjectDefinitionPathThrowsException() {
		$view = $this->buildView();
		$view->setTypoScriptPath('simpleTypes/invalidValue');
		$view->render();
	}

}
?>