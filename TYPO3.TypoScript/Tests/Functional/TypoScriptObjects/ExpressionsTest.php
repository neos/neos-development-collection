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
 * Testcase for Eel expressions in TypoScript
 */
class ExpressionsTest extends AbstractTypoScriptObjectTest {

	public function expressionExamples() {
		return array(
			array('expressions/calculus', 42),
			array('expressions/stringHelper', 'BAR'),
			array('expressions/dateHelper', '14.07.2013 12:14'),
			array('expressions/arrayHelper', 3),
			array('expressions/customHelper', 'Flow'),
			array('expressions/flowQuery', 3)
		);
	}

	/**
	 * @test
	 * @dataProvider expressionExamples
	 */
	public function expressionsWork($path, $expected) {
		$view = $this->buildView();
		$view->setTypoScriptPath($path);
		$view->assign('foo', 'Bar');
		$this->assertSame($expected, $view->render());
	}

}
