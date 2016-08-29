<?php
namespace TYPO3\TypoScript\Tests\Unit\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\I18n\Service;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Resource\Publishing\ResourcePublisher;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\TypoScriptObjects\ResourceUriImplementation;

/**
 * Testcase for the TypoScript ResourceUri object
 */
class ResourceUriImplementationTest extends UnitTestCase
{
    /**
     * @var ResourceUriImplementation
     */
    protected $resourceUriImplementation;

    /**
     * @var Runtime
     */
    protected $mockTsRuntime;

    /**
     * @var ResourcePublisher
     */
    protected $mockResourceManager;

    /**
     * @var Service
     */
    protected $mockI18nService;

    /**
     * @var ControllerContext
     */
    protected $mockControllerContext;

    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    public function setUp()
    {
        $this->mockTsRuntime = $this->getMockBuilder('TYPO3\TypoScript\Core\Runtime')->disableOriginalConstructor()->getMock();

        $this->mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();

        $this->mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
        $this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockActionRequest));

        $this->mockTsRuntime->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));

        $this->resourceUriImplementation = new ResourceUriImplementation($this->mockTsRuntime, 'resourceUri/test', 'TYPO3.TypoScript:ResourceUri');

        $this->mockResourceManager = $this->getMockBuilder('TYPO3\Flow\Resource\ResourceManager')->disableOriginalConstructor()->getMock();
        $this->inject($this->resourceUriImplementation, 'resourceManager', $this->mockResourceManager);

        $this->mockI18nService = $this->getMockBuilder('TYPO3\Flow\I18n\Service')->disableOriginalConstructor()->getMock();
        $this->inject($this->resourceUriImplementation, 'i18nService', $this->mockI18nService);
    }

    /**
     * @test
     * @expectedException \TYPO3\TypoScript\Exception
     */
    public function evaluateThrowsExceptionIfSpecifiedResourceIsInvalid()
    {
        $invalidResource = new \stdClass();
        $this->resourceUriImplementation->evaluate();
    }

    /**
     * @test
     */
    public function evaluateReturnsResourceUriForAGivenResource()
    {
        $validResource = $this->getMockBuilder('TYPO3\Flow\Resource\Resource')->disableOriginalConstructor()->getMock();
        $this->mockTsRuntime->expects($this->atLeastOnce())->method('evaluate')->with('resourceUri/test/resource')->will($this->returnCallback(function ($evaluatePath, $that) use ($validResource) {
            return $validResource;
        }));
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPersistentResourceUri')->with($validResource)->will($this->returnValue('the/resolved/resource/uri'));

        $this->assertSame('the/resolved/resource/uri', $this->resourceUriImplementation->evaluate());
    }

    /**
     * @test
     * @expectedException \TYPO3\TypoScript\Exception
     */
    public function evaluateThrowsExceptionIfNeitherResourceNorPathAreSpecified()
    {
        $this->mockTsRuntime->expects($this->atLeastOnce())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) {
            return null;
        }));

        $this->resourceUriImplementation->evaluate();
    }

    /**
     * @test
     * @expectedException \TYPO3\TypoScript\Exception
     */
    public function evaluateThrowsExceptionIfSpecifiedPathPointsToAPrivateResource()
    {
        $this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'path':
                    return 'resource://Some.Package/Private/SomeResource';
            }
            return null;
        }));

        $this->resourceUriImplementation->evaluate();
    }

    /**
     * @test
     */
    public function evaluateDeterminesCurrentPackageIfARelativePathIsSpecified()
    {
        $this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'path':
                    return 'Relative/Resource/Path';
            }
            return null;
        }));
        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue('Current.Package'));
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPackageResourceUri')->will($this->returnValue('Static/Resources/Packages/Current.Package/Relative/Resource/Path'));

        $this->assertSame('Static/Resources/Packages/Current.Package/Relative/Resource/Path', $this->resourceUriImplementation->evaluate());
    }

    /**
     * @test
     */
    public function evaluateUsesSpecifiedPackageIfARelativePathIsGiven()
    {
        $this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'path':
                    return 'Relative/Resource/Path';
                case 'package':
                    return 'Specified.Package';
            }
            return null;
        }));
        $this->mockActionRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('Current.Package'));
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPackageResourceUri')->will($this->returnValue('Static/Resources/Packages/Specified.Package/Relative/Resource/Path'));

        $this->assertSame('Static/Resources/Packages/Specified.Package/Relative/Resource/Path', $this->resourceUriImplementation->evaluate());
    }


    /**
     * @test
     */
    public function evaluateReturnsResourceUriForAGivenResourcePath()
    {
        $this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'path':
                    return 'resource://Some.Package/Public/SomeResource';
            }
            return null;
        }));
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPackageResourceUri')->will($this->returnValue('Static/Resources/Packages/Some.Package/SomeResource'));

        $this->assertSame('Static/Resources/Packages/Some.Package/SomeResource', $this->resourceUriImplementation->evaluate());
    }

    /**
     * @test
     */
    public function evaluateIgnoresPackagePropertyIfAResourcePathIsGiven()
    {
        $this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'path':
                    return 'resource://Some.Package/Public/SomeResource';
                case 'package':
                    return 'Specified.Package';
            }
            return null;
        }));
        $this->mockActionRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('Current.Package'));
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPackageResourceUri')->will($this->returnValue('Static/Resources/Packages/Some.Package/SomeResource'));

        $this->assertSame('Static/Resources/Packages/Some.Package/SomeResource', $this->resourceUriImplementation->evaluate());
    }

    /**
     * @test
     */
    public function evaluateLocalizesFilenameIfLocalize()
    {
        $this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'localize':
                    return true;
                case 'path':
                    return 'resource://Some.Package/Public/SomeResource';
                case 'package':
                    return 'Specified.Package';
            }
            return null;
        }));
        $this->mockI18nService->expects($this->atLeastOnce())->method('getLocalizedFilename')->will($this->returnValue(array('resource://Some.Package/Public/LocalizedFilename')));
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPackageResourceUri')->will($this->returnValue('Static/Resources/Packages/Some.Package/LocalizedFilename'));

        $this->assertSame('Static/Resources/Packages/Some.Package/LocalizedFilename', $this->resourceUriImplementation->evaluate());
    }
}
