<?php
namespace TYPO3\TYPO3\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the IncludeJavaScript view helper
 *
 */
class IncludeJavaScriptViewHelperTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3\ViewHelpers\IncludeJavaScriptViewHelper
	 */
	protected $viewHelper;

	/**
	 * Set up common mocks and object under test
	 */
	public function setUp() {
		$this->request = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$this->request->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('MyPackage'));
		$this->controllerContext = $this->getMock('TYPO3\FLOW3\MVC\Controller\ControllerContext', array(), array(), '', FALSE);
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
		$this->resourcePublisher = $this->getMock('TYPO3\FLOW3\Resource\Publishing\ResourcePublisher', array(), array(), '', FALSE);
		$this->resourcePublisher->expects($this->any())->method('getStaticResourcesWebBaseUri')->will($this->returnValue('StaticResourceUri/'));
		$this->viewHelper = $this->getAccessibleMock('TYPO3\TYPO3\ViewHelpers\IncludeJavaScriptViewHelper', array('iterateDirectoryRecursively'));
		$renderingContext = new \TYPO3\Fluid\Core\Rendering\RenderingContext();
		$renderingContext->setControllerContext($this->controllerContext);
		$this->viewHelper->setRenderingContext($renderingContext);
		$this->viewHelper->_set('resourcePublisher', $this->resourcePublisher);
	}

	/**
	 * @test
	 */
	public function renderWithoutSubpackageMatchesIncludedFile() {
		$includedFile = $this->getMock('File', array('getPathname'));
		$includedFile->expects($this->any())->method('getPathname')->will($this->returnValue('resource://MyPackage/Public/JavaScript/Foo.js'));
		$otherFile = $this->getMock('File', array('getPathname'));
		$otherFile->expects($this->any())->method('getPathname')->will($this->returnValue('resource://MyPackage/Public/JavaScript/Bar.js'));
		$files = array($includedFile, $otherFile);

		$this->viewHelper->expects($this->atLeastOnce())->method('iterateDirectoryRecursively')->with('resource://MyPackage/Public/JavaScript/')->will($this->returnValue($files));
		$output = $this->viewHelper->render('Foo\.js');
		$this->assertEquals('<script src="StaticResourceUri/Packages/MyPackage/JavaScript/Foo.js"></script>' . chr(10), $output);
	}

	/**
	 * @test
	 */
	public function renderWithSubpackageIgnoresExcludedFile() {
		$includedFile = $this->getMock('File', array('getPathname'));
		$includedFile->expects($this->any())->method('getPathname')->will($this->returnValue('resource://MyPackage/Public/MySubpackage/JavaScript/Foo.js'));
		$excludedFile = $this->getMock('File', array('getPathname'));
		$excludedFile->expects($this->any())->method('getPathname')->will($this->returnValue('resource://MyPackage/Public/MySubpackage/JavaScript/Bar.js'));
		$files = array($includedFile, $excludedFile);

		$this->viewHelper->expects($this->atLeastOnce())->method('iterateDirectoryRecursively')->with('resource://MyPackage/Public/MySubpackage/JavaScript/')->will($this->returnValue($files));
		$output = $this->viewHelper->render('.*\.js', 'Bar.*', NULL, 'MySubpackage');
		$this->assertEquals('<script src="StaticResourceUri/Packages/MyPackage/MySubpackage/JavaScript/Foo.js"></script>' . chr(10), $output);
	}
}
?>