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
 * Testcase for the UriBuilder object
 */
class UriBuilderTest extends AbstractTypoScriptObjectTest {

	/**
	 * @test
	 */
	public function buildRelativeUriToAction() {
		$this->registerRoute(
			'TypoScript functional test',
			'typo3/flow/test/http/foo',
			array(
				'@package' => 'TYPO3.Flow',
				'@subpackage' => 'Tests\Functional\Http\Fixtures',
				'@controller' => 'Foo',
				'@action' => 'index',
				'@format' => 'html'
			));

		$view = $this->buildView();
		$view->setTypoScriptPath('uriBuilder/foo');
		$this->assertContains('/typo3/flow/test/http/foo', $view->render());
	}
}
