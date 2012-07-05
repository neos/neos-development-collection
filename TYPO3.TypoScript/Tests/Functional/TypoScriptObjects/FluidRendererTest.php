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
 * Testcase for the TypoScript View
 *
 */
class FluidRendererTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function basicFluidTemplateCanBeUsedForRendering() {
		$view = $this->buildView();

		$view->setTypoScriptPath('fluidRenderer/basicTemplate');
		$this->assertEquals('Test Templatefoo', $view->render());
	}

	/**
	 * @test
	 */
	public function customPartialPathCanBeSetOnRendering() {
		$view = $this->buildView();

		$view->setTypoScriptPath('fluidRenderer/partial');
		$this->assertEquals('Test Template--partial contents', $view->render());
	}

	/**
	 * @test
	 */
	public function customLayoutPathCanBeSetOnRendering() {
		$view = $this->buildView();

		$view->setTypoScriptPath('fluidRenderer/layout');
		$this->assertEquals('layout start -- Test Template -- layout end', $view->render());
	}

	/**
	 * Helper to build a TypoScript view object
	 *
	 * @return \TYPO3\TypoScript\View\TypoScriptView
	 */
	protected function buildView() {
		$view = new \TYPO3\TypoScript\View\TypoScriptView();

		$mockControllerContext = $this->getMockBuilder('TYPO3\FLOW3\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
		$view->setControllerContext($mockControllerContext);
		$view->disableFallbackView();
		$view->setPackageKey('TYPO3.TypoScript');
		$view->assign('fixtureDirectory', __DIR__ . '/Fixtures/');

		\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($view, 'typoScriptPathPattern', __DIR__ . '/Fixtures', TRUE);

		return $view;
	}
}
?>