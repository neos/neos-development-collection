<?php
namespace TYPO3\Neos\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the IncludeJavaScript view helper
 * @deprecated Same as the ViewHelper this test is for
 */
class IncludeJavaScriptViewHelperTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Neos\ViewHelpers\IncludeJavaScriptViewHelper
     */
    protected $viewHelper;

    /**
     * Set up common mocks and object under test
     */
    public function setUp()
    {
        $this->request = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
        $this->request->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('MyPackage'));
        $this->controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', false);
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->resourcePublisher = $this->getMock('TYPO3\Flow\Resource\Publishing\ResourcePublisher', array(), array(), '', false);
        $this->resourcePublisher->expects($this->any())->method('getStaticResourcesWebBaseUri')->will($this->returnValue('StaticResourceUri/'));
        $this->viewHelper = $this->getAccessibleMock('TYPO3\Neos\ViewHelpers\IncludeJavaScriptViewHelper', array('iterateDirectoryRecursively'));
        $renderingContext = new \TYPO3\Fluid\Core\Rendering\RenderingContext();
        $renderingContext->setControllerContext($this->controllerContext);
        $this->viewHelper->setRenderingContext($renderingContext);
        $this->viewHelper->_set('resourcePublisher', $this->resourcePublisher);
    }

    /**
     * @test
     */
    public function renderWithoutSubpackageMatchesIncludedFile()
    {
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
    public function renderWithSubpackageIgnoresExcludedFile()
    {
        $includedFile = $this->getMock('File', array('getPathname'));
        $includedFile->expects($this->any())->method('getPathname')->will($this->returnValue('resource://MyPackage/Public/MySubpackage/JavaScript/Foo.js'));
        $excludedFile = $this->getMock('File', array('getPathname'));
        $excludedFile->expects($this->any())->method('getPathname')->will($this->returnValue('resource://MyPackage/Public/MySubpackage/JavaScript/Bar.js'));
        $files = array($includedFile, $excludedFile);

        $this->viewHelper->expects($this->atLeastOnce())->method('iterateDirectoryRecursively')->with('resource://MyPackage/Public/MySubpackage/JavaScript/')->will($this->returnValue($files));
        $output = $this->viewHelper->render('.*\.js', 'Bar.*', null, 'MySubpackage');
        $this->assertEquals('<script src="StaticResourceUri/Packages/MyPackage/MySubpackage/JavaScript/Foo.js"></script>' . chr(10), $output);
    }
}
