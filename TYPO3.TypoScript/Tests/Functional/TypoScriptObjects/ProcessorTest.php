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
class ProcessorTest extends AbstractTypoScriptObjectTest {

	/**
	 * @test
	 */
	public function basicProcessorsWork() {
		$this->assertMultipleTypoScriptPaths('Hello World foo', 'processors/newSyntax/basicProcessor/valueWithNested');
	}

	/**
	 * @test
	 */
	public function extendedSyntaxProcessorsWork() {
		$this->assertMultipleTypoScriptPaths('Hello World foo', 'processors/newSyntax/extendedSyntaxProcessor/valueWithNested');
	}

	/**
	 * Data Provider for processorsCanBeUnset
	 *
	 * @return array
	 */
	public function dataProviderForUnsettingProcessors() {
		return array(
			array('processors/newSyntax/unset/simple'),
			array('processors/newSyntax/unset/prototypes1'),
			array('processors/newSyntax/unset/prototypes2'),
			array('processors/newSyntax/unset/nestedScope/prototypes3')
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderForUnsettingProcessors
	 */
	public function processorsCanBeUnset($path) {
		$view = $this->buildView();
		$view->setTypoScriptPath($path);
		$this->assertEquals('Foobaz', $view->render());
	}

}
