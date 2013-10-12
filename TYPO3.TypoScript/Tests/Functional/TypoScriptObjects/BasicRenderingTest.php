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
 * Testcase for basic TypoScript rendering
 *
 */
class BasicRenderingTest extends AbstractTypoScriptObjectTest {

	/**
	 * @test
	 */
	public function basicRendering() {
		$view = $this->buildView();
		$view->setTypoScriptPath('basicRendering/test');
		$this->assertEquals('XHello World', $view->render());
	}

	/**
	 * @test
	 */
	public function basicRenderingReusingTypoScriptVariables() {
		$view = $this->buildView();
		$view->setTypoScriptPath('basicRendering/reuseTypoScriptVariables');
		$this->assertEquals('XHello World', $view->render());
	}

	/**
	 * @test
	 */
	public function basicRenderingReusingTypoScriptVariablesWithEel() {
		$view = $this->buildView();
		$view->setTypoScriptPath('basicRendering/reuseTypoScriptVariablesWithEel');
		$this->assertEquals('XHello World', $view->render());
	}

	/**
	 * @test
	 */
	public function complexExample() {
		$view = $this->buildView();
		$view->setTypoScriptPath('basicRendering/complexExample/toRender');
		$this->assertEquals('Static string post', $view->render());
	}

	/**
	 * @test
	 */
	public function complexExample2() {
		$view = $this->buildView();
		$view->setTypoScriptPath('basicRendering/complexExample2/toRender');
		$this->assertEquals('Static string post', $view->render());
	}

}
