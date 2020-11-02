<?php

namespace Neos\SiteKickstarter\Tests\Unit\Service;

/*
 * This file is part of the Neos.SiteKickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Reader;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\SiteKickstarter\Service\SitePackageGeneratorNameService;
use Neos\SiteKickstarter\Tests\Unit\Service\Fixtures\AnnotatedSitePackageGenerator;
use Neos\SiteKickstarter\Tests\Unit\Service\Fixtures\BlankSitePackageGenerator;

require_once __DIR__ . '/Fixtures/AnnotatedSitePackageGenerator.php';
require_once __DIR__ . '/Fixtures/BlankSitePackageGenerator.php';

class SitePackageGeneratorNameServiceTest extends UnitTestCase
{
    /**
     * @var SitePackageGeneratorNameService
     */
    protected $sitePackageGeneratorNameService;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    protected function setUp(): void
    {
        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();

        $this->sitePackageGeneratorNameService = new  SitePackageGeneratorNameService();
        $this->inject($this->sitePackageGeneratorNameService, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function getNameOfSitePackageGeneratorByImplementedGetFunction()
    {
        $this->mockObjectManager->expects(self::any())->method('get')->will(self::returnCallback(function ($className) {
            return new AnnotatedSitePackageGenerator();
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
        $this->mockObjectManager->expects(self::any())->method('get')->will(self::returnCallback(function ($className) {
            return new BlankSitePackageGenerator();
        }));

        $this->assertEquals(
            $this->sitePackageGeneratorNameService->getNameOfSitePackageGenerator(BlankSitePackageGenerator::class),
            BlankSitePackageGenerator::class
        );
    }
}
