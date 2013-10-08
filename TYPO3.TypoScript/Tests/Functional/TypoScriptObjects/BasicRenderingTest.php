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
	 * The view cannot be rendered since it is broken
	 * in this case an exception handler is called.
	 * It takes the exceptions and shall produce some log message.
	 *
	 * The default handler for the tests rethrows the exception
	 * TODO: test different exception handlers
	 *
	 * @test
	 * @expectedException TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	public function basicRenderingCrashing() {
		$view = $this->buildView();
		$view->setTypoScriptPath('basicRendering/crashing');
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

	/**
	 * @test
	 */
	public function plainValueCanBeOverridden() {
		$this->assertMultipleTypoScriptPaths('overridden', 'basicRendering/overridePlainValueWith');
	}

	/**
	 * @test
	 */
	public function eelExpressionCanBeOverridden() {
		$this->assertMultipleTypoScriptPaths('overridden', 'basicRendering/overrideEelWith');
	}

	/**
	 * @test
	 */
	public function typoScriptCanBeOverridden() {
		$this->assertMultipleTypoScriptPaths('overridden', 'basicRendering/overrideTypoScriptWith');
	}

	/**
	 * @test
	 */
	public function contentIsNotTrimmed() {
		$view = $this->buildView();
		$view->setTypoScriptPath('basicRendering/contentIsNotTrimmed');
		$this->assertEquals('X I want to have some space after me ', $view->render());
	}
}