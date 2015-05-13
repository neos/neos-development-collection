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
class NestedOverwritesAndProcessorsTest extends AbstractTypoScriptObjectTest {

	/**
	 * @test
	 */
	public function overwritingSimpleValueWithProcessorWorks() {
		$view = $this->buildView();
		$view->setTypoScriptPath('nestedOverwritesAndProcessors/deepProcessorAppliesToSimpleValue');
		$this->assertEquals('<div class="Xclass processed" tea="green"></div>', $view->render());
	}

	/**
	 * @test
	 */
	public function applyingProcessorToExpressionWorks() {
		$view = $this->buildView();
		$view->setTypoScriptPath('nestedOverwritesAndProcessors/deepProcessorAppliesToEel');
		$this->assertEquals('<div class="Xclass" tea="green infused"></div>', $view->render());
	}

	/**
	 * @test
	 */
	public function applyingProcessorToNonExistingValueWorks() {
		$view = $this->buildView();
		$view->setTypoScriptPath('nestedOverwritesAndProcessors/deepProcessorAppliesWithNoBaseValue');
		$this->assertEquals('<div class="Xclass" tea="green" coffee="harvey"></div>', $view->render());
	}
}