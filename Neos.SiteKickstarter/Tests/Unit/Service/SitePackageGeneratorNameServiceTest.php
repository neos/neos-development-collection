<?php

namespace Neos\SiteKickstarter\Tests\Service;

use Doctrine\Common\Annotations\Reader;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\SiteKickstarter\Annotation\SitePackageGenerator;
use Neos\SiteKickstarter\Service\SitePackageGeneratorNameService;
use Neos\SiteKickstarter\Tests\Fixtures\AnnotatedSitePackageGenerator;
use Neos\SiteKickstarter\Tests\Fixtures\BlankSitePackageGenerator;

class SitePackageGeneratorNameServiceTest extends UnitTestCase
{
    /**
     * @var ReflectionService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockReflectionService;

    /**
     * @var Reader|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockAnnotationReader;

    /**
     * @var SitePackageGeneratorNameService
     */
    protected $sitePackageGeneratorNameService;

    protected function setUp(): void
    {
        $this->sitePackageGeneratorNameService = new SitePackageGeneratorNameService();
        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->inject($this->sitePackageGeneratorNameService, 'reflectionService', $this->mockReflectionService);
    }

    /**
     * @test
     */
    public function getNameOfSitePackageGeneratorByAnnotation()
    {
        $this->mockReflectionService->expects(self::any())->method('getClassAnnotation')->will(self::returnCallback(function ($generatorClass, $annotationClass) {
            return new SitePackageGenerator([
                'generatorName' => 'AnnotatedSitePackageGenerator'
            ]);
        }));

        $this->assertEquals(
            $this->sitePackageGeneratorNameService->getNameOfSitePackageGenerator(AnnotatedSitePackageGenerator::class),
            'AnnotatedSitePackageGenerator'
        );
    }

    /**
     * @test
     */
    public function getNameOfSitePackageGeneratorByDefault()
    {
        $this->assertEquals(
            $this->sitePackageGeneratorNameService->getNameOfSitePackageGenerator(BlankSitePackageGenerator::class),
            BlankSitePackageGenerator::class
        );
    }
}