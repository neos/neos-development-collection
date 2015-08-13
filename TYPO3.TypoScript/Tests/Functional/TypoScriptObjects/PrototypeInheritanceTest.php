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
 * Prototypical Inheritance Test
 */
class PrototypeInheritanceTest extends AbstractTypoScriptObjectTest {

	/**
	 * @test
	 */
	public function baseClassHasModifiedValue() {
		$view = $this->buildView();
		$view->setTypoScriptPath('prototypeInheritance/base');
		$this->assertEquals('BaseModified', $view->render());
	}

	/**
	 * @test
	 */
	public function subWithOverrideHasOverriddenValue() {
		$view = $this->buildView();
		$view->setTypoScriptPath('prototypeInheritance/subWithOverride');
		$this->assertEquals('Sub', $view->render());
	}

	/**
	 * @test
	 */
	public function subWithoutOverrideHasModifiedBaseValue() {
		$view = $this->buildView();
		$view->setTypoScriptPath('prototypeInheritance/subWithoutOverride');
		$this->assertEquals('BaseModified', $view->render());
	}

	/**
	 * @test
	 */
	public function advancedBaseObjectHasModifiedValue() {
		$view = $this->buildView();
		$view->setTypoScriptPath('prototypeInheritanceAdvanced/base');
		$this->assertEquals('prepend_beforeOverride|value_from_nested_prototype|append_afterOverride', $view->render());
	}

	/**
	 * @test
	 */
	public function advancedSubWithoutOverrideHasModifiedBaseValue() {
		$view = $this->buildView();
		$view->setTypoScriptPath('prototypeInheritanceAdvanced/subWithoutOverride');
		$this->assertEquals('prepend_beforeOverride|value_from_nested_prototype|append_afterOverride', $view->render());
	}

	/**
	 * @test
	 */
	public function advancedSubWithOverrideHasModifiedBaseValue() {
		$view = $this->buildView();
		$view->setTypoScriptPath('prototypeInheritanceAdvanced/subWithOverride');
		$this->assertEquals('prepend_inSub|value_from_nested_prototype|append_afterOverride', $view->render());
	}
}
