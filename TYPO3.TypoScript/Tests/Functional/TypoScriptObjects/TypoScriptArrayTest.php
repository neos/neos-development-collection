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
 * Testcase for the TypoScript Array
 *
 */
class TypoScriptArrayTest extends AbstractTypoScriptObjectTest {

	/**
	 * @test
	 */
	public function basicOrderingWorks() {
		$view = $this->buildView();

		$view->setTypoScriptPath('array/basicOrdering');
		$this->assertEquals('Xtest10Xtest100', $view->render());
	}

	/**
	 * @test
	 */
	public function positionalOrderingWorks() {
		$view = $this->buildView();

		$view->setTypoScriptPath('array/positionalOrdering');
		$this->assertEquals('XbeforeXmiddleXafter', $view->render());
	}

	/**
	 * @test
	 */
	public function startEndOrderingWorks() {
		$view = $this->buildView();

		$view->setTypoScriptPath('array/startEndOrdering');
		$this->assertEquals('XbeforeXmiddleXafter', $view->render());
	}

	/**
	 * @test
	 */
	public function advancedStartEndOrderingWorks() {
		$view = $this->buildView();

		$view->setTypoScriptPath('array/advancedStartEndOrdering');
		$this->assertEquals('XeXdXfoobarXfXgX100XbXaXc', $view->render());
	}

	/**
	 * @test
	 */
	public function ignoredPropertiesWork() {
		$view = $this->buildView();

		$view->setTypoScriptPath('array/ignoreProperties');
		$this->assertEquals('XbeforeXafter', $view->render());
	}

}
