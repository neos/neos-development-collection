<?php
namespace Neos\Neos\Tests\Unit\Aspects;

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
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Flow\Aop\Advice\AdviceChain;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Aspects\FusionCachingAspect;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test case for the FusionCachingAspect
 */
class FusionCachingAspectTest extends UnitTestCase
{

    /**
     * @var FusionCachingAspect
     */
    private $fusionCachingAspect;

    /**
     * @var JoinPointInterface|MockObject
     */
    private $mockJoinPoint;

    /**
     * @var AdviceChain|MockObject
     */
    private $mockAdviceChain;

    /**
     * @var TraversableNodeInterface|MockObject
     */
    private $mockStartNode;

    /**
     * @var VariableFrontend|MockObject
     */
    private $mockFusionCache;

    /**
     * @var SiteRepository|MockObject
     */
    private $mockSiteRepository;

    /**
     * @var Site|MockObject
     */
    private $mockSite;

    public function setUp(): void
    {
        parent::setUp();

        $this->fusionCachingAspect = new FusionCachingAspect();

        $this->mockJoinPoint = $this->getMockBuilder(JoinPointInterface::class)->getMock();
        $this->mockStartNode = $this->getMockBuilder(TraversableNodeInterface::class)->getMock();
        $this->mockJoinPoint->method('getMethodArgument')->with('startNode')->willReturn($this->mockStartNode);

        $this->mockAdviceChain = $this->getMockBuilder(AdviceChain::class)->disableOriginalConstructor()->getMock();
        $this->mockJoinPoint->method('getAdviceChain')->willReturn($this->mockAdviceChain);

        $this->mockFusionCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->fusionCachingAspect, 'fusionCache', $this->mockFusionCache);

        $this->mockSiteRepository = $this->getMockBuilder(SiteRepository::class)->setMethods(['findOneByNodeName'])->disableOriginalConstructor()->getMock();

        $this->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $this->mockSite->method('getSiteResourcesPackageKey')->willReturn('Some.ResourcePackage');
        $this->mockSiteRepository->method('findOneByNodeName')->willReturn($this->mockSite);

        $this->inject($this->fusionCachingAspect, 'siteRepository', $this->mockSiteRepository);
    }

    /**
     * @test
     */
    public function cacheGetMergedFusionObjectTreeNormalizesCacheIdentifier()
    {
        $mockSiteRepository = $this->getMockBuilder(SiteRepository::class)->setMethods(['findOneByNodeName'])->disableOriginalConstructor()->getMock();
        $mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockSite->method('getSiteResourcesPackageKey')->willReturn('Some.Package-With_SpecialChars');
        $mockSiteRepository->method('findOneByNodeName')->willReturn($mockSite);

        $this->inject($this->fusionCachingAspect, 'siteRepository', $mockSiteRepository);

        $this->mockFusionCache->expects(self::once())->method('has')->with('Some_Package-With_SpecialChars');

        $this->fusionCachingAspect->cacheGetMergedFusionObjectTree($this->mockJoinPoint);
    }

    /**
     * @test
     */
    public function cacheGetMergedFusionObjectTreeAddsResultToCache()
    {
        $mergedObjectTree = ['some' => 'Fusion tree'];
        $cacheIdentifier = 'Some_ResourcePackage';

        $this->mockFusionCache->expects(self::once())->method('has')->with($cacheIdentifier)->willReturn(false);
        $this->mockAdviceChain->expects(self::once())->method('proceed')->willReturn($mergedObjectTree);
        $this->mockFusionCache->expects(self::once())->method('set')->with($cacheIdentifier, $mergedObjectTree);

        self::assertSame($mergedObjectTree, $this->fusionCachingAspect->cacheGetMergedFusionObjectTree($this->mockJoinPoint));
    }

    /**
     * @test
     */
    public function cacheGetMergedFusionObjectTreeReturnsResultsFromCacheIfExists()
    {
        $mergedObjectTree = ['some' => 'Fusion tree'];
        $cacheIdentifier = 'Some_ResourcePackage';

        $this->mockFusionCache->expects(self::once())->method('has')->with($cacheIdentifier)->willReturn(true);
        $this->mockAdviceChain->expects(self::never())->method('proceed');
        $this->mockFusionCache->expects(self::once())->method('get')->with($cacheIdentifier)->willReturn($mergedObjectTree);

        self::assertSame($mergedObjectTree, $this->fusionCachingAspect->cacheGetMergedFusionObjectTree($this->mockJoinPoint));
    }

    /**
     * This tests addresses the bug described with https://github.com/neos/neos-development-collection/issues/3191
     *
     * Previously the cache identifier was determined from the context of the given "startNode". This led to an invalid
     * identifier when rendering a node from a node that is bound to the context of a different site
     *
     * @test
     */
    public function cacheGetMergedFusionObjectTreeIgnoresTheNodeContextWhenResolvingTheCacheIdentifier()
    {
        $mockContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();

        $mockContextSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContextSite->method('getSiteResourcesPackageKey')->willReturn('ContextSite.ResourcePackage');
        $mockContext->method('getCurrentSite')->willReturn($mockContextSite);

        $this->mockStartNode = $this->getMockBuilder(NodeInterface::class)->getMock();

        $this->mockFusionCache->method('set')->willReturnCallback(static function (string $cacheIdentifier) {
            self::assertSame('Some_ResourcePackage', $cacheIdentifier);
        });

        $this->fusionCachingAspect->cacheGetMergedFusionObjectTree($this->mockJoinPoint);
    }
}
