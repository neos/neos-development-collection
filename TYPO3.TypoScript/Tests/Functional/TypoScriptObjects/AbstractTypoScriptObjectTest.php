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
abstract class AbstractTypoScriptObjectTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * Helper to build a TypoScript view object
	 *
	 * @return \TYPO3\TypoScript\View\TypoScriptView
	 */
	protected function buildView() {
		$view = new \TYPO3\TypoScript\View\TypoScriptView();

		$mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
		$view->setControllerContext($mockControllerContext);
		$view->disableFallbackView();
		$view->setPackageKey('TYPO3.TypoScript');
		$view->assign('fixtureDirectory', __DIR__ . '/Fixtures/');

		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($view, 'typoScriptPathPattern', __DIR__ . '/Fixtures', TRUE);

		return $view;
	}
}
?>