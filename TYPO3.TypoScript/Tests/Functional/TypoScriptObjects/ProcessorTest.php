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
	public function runtimeAwareProcessorsWork() {
		$view = $this->buildView();
		$view->assign('var1', 'var1');
		$view->setTypoScriptPath('processors/runtimeAware');
		$this->assertEquals('Xvar1', $view->render());
	}

	/**
	 * @test
	 */
	public function runtimeAwareProcessorsWorkWhenProcessorIsAddedToPrototype() {
		$view = $this->buildView();
		$view->assign('var1', 'var1');
		$view->setTypoScriptPath('processors/runtimeAwarePrototype');
		$this->assertEquals('Xvar1', $view->render());
	}
}
?>