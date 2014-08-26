<?php
namespace TYPO3\Neos\Tests\Unit\ViewHelpers\Uri;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\ViewHelpers\Uri\ModuleViewHelper;

/**
 */
class ModuleViewHelperTest extends UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|ModuleViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|UriBuilder
	 */
	protected $uriBuilder;

	/**
	 */
	protected function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getMock('TYPO3\Neos\ViewHelpers\Uri\ModuleViewHelper', array('setMainRequestToUriBuilder'));
		$this->uriBuilder = $this->getMock('TYPO3\Flow\Mvc\Routing\UriBuilder');
		$this->inject($this->viewHelper, 'uriBuilder', $this->uriBuilder);
	}

	/**
	 * @test
	 */
	public function callingRenderAssignsVariablesCorrectlyToUriBuilder() {
		$this->uriBuilder->expects($this->once())->method('setSection')->with('section')->will($this->returnSelf());
		$this->uriBuilder->expects($this->once())->method('setArguments')->with(array('additionalParams'))->will($this->returnSelf());
		$this->uriBuilder->expects($this->once())->method('setArgumentsToBeExcludedFromQueryString')->with(array('argumentsToBeExcludedFromQueryString'))->will($this->returnSelf());
		$this->uriBuilder->expects($this->once())->method('setFormat')->with('format')->will($this->returnSelf());

		$expectedModifiedArguments = array(
			'module' => 'the/path',
			'moduleArguments' => array('arguments', '@action' => 'action')
		);

		$this->uriBuilder->expects($this->once())->method('uriFor')->with('index', $expectedModifiedArguments);

		// fallback for the method chaining of the URI builder
		$this->uriBuilder->expects($this->any())->method($this->anything())->will($this->returnValue($this->uriBuilder));

		$this->viewHelper->render(
			'the/path',
			'action',
			array('arguments'),
			'section',
			'format',
			array('additionalParams'),
			TRUE, // `addQueryString`,
			array('argumentsToBeExcludedFromQueryString')
		);
	}

}
