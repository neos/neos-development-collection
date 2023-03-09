<?php
namespace Neos\Neos\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\FusionConfiguration;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Service\FusionConfigurationCache;
use PHPUnit\Framework\MockObject\MockObject;

class FusionConfigurationCacheTest extends UnitTestCase
{

    /**
     * @var FusionConfigurationCache
     */
    private $fusionConfigurationCache;

    /**
     * @var VariableFrontend|MockObject
     */
    private $mockFusionCache;

    /**
     * @var Site|MockObject
     */
    private $mockSite;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockFusionCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();

        $this->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $this->mockSite->method('getSiteResourcesPackageKey')->willReturn('Some.ResourcePackage');

        $this->fusionConfigurationCache = new FusionConfigurationCache(
            cache: $this->mockFusionCache,
            enabled: true
        );
    }

    /**
     * @test
     */
    public function cacheGetMergedFusionObjectTreeNormalizesCacheIdentifier()
    {
        $mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockSite->method('getSiteResourcesPackageKey')->willReturn('Some.Package-With_SpecialChars');

        $this->mockFusionCache->expects(self::once())->method('has')->with('Some_Package-With_SpecialChars');

        $this->fusionConfigurationCache->cacheFusionConfigurationBySite($mockSite, fn () => FusionConfiguration::fromArray([]));
    }

    /**
     * @test
     */
    public function cacheGetMergedFusionObjectTreeAddsResultToCache()
    {
        $mergedObjectTree = ['some' => 'Fusion tree'];
        $cacheIdentifier = 'Some_ResourcePackage';

        $this->mockFusionCache->expects(self::once())->method('has')->with($cacheIdentifier)->willReturn(false);

        $expectedReturn = FusionConfiguration::fromArray($mergedObjectTree);

        self::assertSame($expectedReturn, $this->fusionConfigurationCache->cacheFusionConfigurationBySite(
            $this->mockSite,
            fn () => $expectedReturn
        ));
    }

    /**
     * @test
     */
    public function cacheGetMergedFusionObjectTreeReturnsResultsFromCacheIfExists()
    {
        $mergedObjectTree = ['some' => 'Fusion tree'];
        $cacheIdentifier = 'Some_ResourcePackage';

        $this->mockFusionCache->expects(self::once())->method('has')->with($cacheIdentifier)->willReturn(true);
        $this->mockFusionCache->expects(self::once())->method('get')->with($cacheIdentifier)->willReturn($mergedObjectTree);

        self::assertEquals(FusionConfiguration::fromArray($mergedObjectTree), $this->fusionConfigurationCache->cacheFusionConfigurationBySite(
            $this->mockSite,
            fn () => self::fail("FusionConfiguration factory called.")
        ));
    }
}
