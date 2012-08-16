<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TypoScript".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Case TS object
 *
 */
class CaseTest extends AbstractTypoScriptObjectTest {

	/**
	 * @test
	 */
	public function classicMatchingWorks($path = 'case/classicMatching') {
		$view = $this->buildView();
		$view->assign('cond', TRUE);
		$view->setTypoScriptPath($path);
		$this->assertEquals('Xtestconditiontrue', $view->render());

		$view->assign('cond', FALSE);
		$this->assertEquals('Xtestconditionfalse', $view->render());
	}

	/**
	 * @test
	 */
	public function numericMatchingWorks() {
		$this->classicMatchingWorks('case/numericMatching');
	}

	/**
	 * @test
	 */
	public function positionalMatchingWorks() {
		$this->classicMatchingWorks('case/positionalMatching');
	}
}
?>