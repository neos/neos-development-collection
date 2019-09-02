<?php
namespace Neos\Fusion\Tests\Unit\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n\Service;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception;
use Neos\Fusion\FusionObjects\ResourceUriImplementation;

/**
 * Testcase for the Fusion ResourceUri object
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
    protected $mockRuntime;

    /**
     * @var ResourceManager
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

    public function setUp(): void
    {
        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $this->mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockControllerContext->expects(self::any())->method('getRequest')->will(self::returnValue($this->mockActionRequest));

        $this->mockRuntime->expects(self::any())->method('getControllerContext')->will(self::returnValue($this->mockControllerContext));

        $this->resourceUriImplementation = new ResourceUriImplementation($this->mockRuntime, 'resourceUri/test', 'Neos.Fusion:ResourceUri');

        $this->mockResourceManager = $this->getMockBuilder(ResourceManager::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->resourceUriImplementation, 'resourceManager', $this->mockResourceManager);

        $this->mockI18nService = $this->getMockBuilder(Service::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->resourceUriImplementation, 'i18nService', $this->mockI18nService);
    }

    /**
     * @test
     */
    public function evaluateThrowsExceptionIfSpecifiedResourceIsInvalid()
    {
        $this->expectException(Exception::class);
        $invalidResource = new \stdClass();
        $this->resourceUriImplementation->evaluate();
    }

    /**
     * @test
     */
    public function evaluateReturnsResourceUriForAGivenResource()
    {
        $validResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $this->mockRuntime->expects(self::atLeastOnce())->method('evaluate')->with('resourceUri/test/resource')->will(self::returnCallback(function ($evaluatePath, $that) use ($validResource) {
            return $validResource;
        }));
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPersistentResourceUri')->with($validResource)->will(self::returnValue('the/resolved/resource/uri'));

        self::assertSame('the/resolved/resource/uri', $this->resourceUriImplementation->evaluate());
    }

    /**
     * @test
     */
    public function evaluateThrowsExceptionIfNeitherResourceNorPathAreSpecified()
    {
        $this->expectException(Exception::class);
        $this->mockRuntime->expects(self::atLeastOnce())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) {
            return null;
        }));

        $this->resourceUriImplementation->evaluate();
    }

    /**
     * @test
     */
    public function evaluateThrowsExceptionIfSpecifiedPathPointsToAPrivateResource()
    {
        $this->expectException(Exception::class);
        $this->mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) {
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
        $this->mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'path':
                    return 'Relative/Resource/Path';
            }
            return null;
        }));
        $this->mockActionRequest->expects(self::atLeastOnce())->method('getControllerPackageKey')->will(self::returnValue('Current.Package'));
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPackageResourceUri')->will(self::returnValue('Static/Resources/Packages/Current.Package/Relative/Resource/Path'));

        self::assertSame('Static/Resources/Packages/Current.Package/Relative/Resource/Path', $this->resourceUriImplementation->evaluate());
    }

    /**
     * @test
     */
    public function evaluateUsesSpecifiedPackageIfARelativePathIsGiven()
    {
        $this->mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'path':
                    return 'Relative/Resource/Path';
                case 'package':
                    return 'Specified.Package';
            }
            return null;
        }));
        $this->mockActionRequest->expects(self::any())->method('getControllerPackageKey')->will(self::returnValue('Current.Package'));
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPackageResourceUri')->will(self::returnValue('Static/Resources/Packages/Specified.Package/Relative/Resource/Path'));

        self::assertSame('Static/Resources/Packages/Specified.Package/Relative/Resource/Path', $this->resourceUriImplementation->evaluate());
    }


    /**
     * @test
     */
    public function evaluateReturnsResourceUriForAGivenResourcePath()
    {
        $this->mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'path':
                    return 'resource://Some.Package/Public/SomeResource';
            }
            return null;
        }));
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPackageResourceUri')->will(self::returnValue('Static/Resources/Packages/Some.Package/SomeResource'));

        self::assertSame('Static/Resources/Packages/Some.Package/SomeResource', $this->resourceUriImplementation->evaluate());
    }

    /**
     * @test
     */
    public function evaluateIgnoresPackagePropertyIfAResourcePathIsGiven()
    {
        $this->mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) {
            $relativePath = str_replace('resourceUri/test/', '', $evaluatePath);
            switch ($relativePath) {
                case 'path':
                    return 'resource://Some.Package/Public/SomeResource';
                case 'package':
                    return 'Specified.Package';
            }
            return null;
        }));
        $this->mockActionRequest->expects(self::any())->method('getControllerPackageKey')->will(self::returnValue('Current.Package'));
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPackageResourceUri')->will(self::returnValue('Static/Resources/Packages/Some.Package/SomeResource'));

        self::assertSame('Static/Resources/Packages/Some.Package/SomeResource', $this->resourceUriImplementation->evaluate());
    }

    /**
     * @test
     */
    public function evaluateLocalizesFilenameIfLocalize()
    {
        $this->mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) {
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
        $this->mockI18nService->expects(self::atLeastOnce())->method('getLocalizedFilename')->will(self::returnValue(['resource://Some.Package/Public/LocalizedFilename']));
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPackageResourceUri')->will(self::returnValue('Static/Resources/Packages/Some.Package/LocalizedFilename'));

        self::assertSame('Static/Resources/Packages/Some.Package/LocalizedFilename', $this->resourceUriImplementation->evaluate());
    }
}
