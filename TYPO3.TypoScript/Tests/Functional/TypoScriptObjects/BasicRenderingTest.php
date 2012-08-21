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
	public function deprecatedImplementationClassName() {
		$view = $this->buildView();
		$view->setTypoScriptPath('basicRendering/deprecatedImplementationClassName');
		$this->assertEquals('XHello World', $view->render());
	}

}
?>