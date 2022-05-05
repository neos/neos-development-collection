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

use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * Testcase for the ContentContextFactory
 *
 */
class ContentContextFactoryTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function createWillSetDomainAndSiteFromCurrentRequestIfNotGiven()
    {
        $mockDomainRepository = $this->getMockBuilder(DomainRepository::class)->disableOriginalConstructor()->getMock();

        $mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();

        $mockDomain = $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock();
        $mockDomain->expects(self::atLeastOnce())->method('getSite')->will(self::returnValue($mockSite));
        $mockDomainRepository->expects(self::atLeastOnce())->method('findOneByActiveRequest')->will(self::returnValue($mockDomain));

        $mockSiteRepository = $this->getMockBuilder(SiteRepository::class)->disableOriginalConstructor()->getMock();
        $mockSiteRepository->expects(self::any())->method('findFirstOnline')->will(self::returnValue(null));

        $contentContextFactory = $this->getMockBuilder(ContentContextFactory::class)->setMethods([
            'validateContextProperties',
            'mergeDimensionValues',
            'mergeTargetDimensionContextProperties',
            'getIdentifier'
        ])->disableOriginalConstructor()->getMock();
        $contentContextFactory->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('abc'));

        $this->inject($contentContextFactory, 'domainRepository', $mockDomainRepository);
        $this->inject($contentContextFactory, 'siteRepository', $mockSiteRepository);
        $this->inject($contentContextFactory, 'now', new \DateTime());

        /** @var ContentContext $context */
        $context = $contentContextFactory->create(['workspaceName' => 'user-test']);
        self::assertEquals($mockSite, $context->getCurrentSite());
        self::assertEquals($mockDomain, $context->getCurrentDomain());
    }
}
