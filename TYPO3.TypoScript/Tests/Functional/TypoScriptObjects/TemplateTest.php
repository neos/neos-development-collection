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
 * Testcase for the TypoScript Template Object
 *
 */
class TemplateTest extends AbstractTypoScriptObjectTest {

	/**
	 * @test
	 */
	public function basicFluidTemplateCanBeUsedForRendering() {
		$view = $this->buildView();
		$view->setTypoScriptPath('template/basicTemplate');
		$this->assertEquals('Test Templatefoo', $view->render());
	}

	/**
	 * @test
	 */
	public function basicFluidTemplateContainsEelVariables() {
		$view = $this->buildView();
		$view->setTypoScriptPath('template/basicTemplateWithEelVariable');
		$this->assertEquals('Test Templatefoobar', $view->render());
	}

	/**
	 * @test
	 */
	public function customPartialPathCanBeSetOnRendering() {
		$view = $this->buildView();
		$view->setTypoScriptPath('template/partial');
		$this->assertEquals('Test Template--partial contents', $view->render());
	}

	/**
	 * @test
	 */
	public function customLayoutPathCanBeSetOnRendering() {
		$view = $this->buildView();
		$view->setTypoScriptPath('template/layout');
		$this->assertEquals('layout start -- Test Template -- layout end', $view->render());
	}

	/**
	 * @test
	 */
	public function typoScriptExceptionInObjectAccessIsHandledCorrectly() {
		$view = $this->buildView();
		$view->setTypoScriptPath('template/offsetAccessException');
		$this->assertStringStartsWith('Test TemplateException while rendering template', $view->render());
	}

}
