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
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\ContentContextFactory;

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
        $mockDomain->expects($this->atLeastOnce())->method('getSite')->will($this->returnValue($mockSite));
        $mockDomainRepository->expects($this->atLeastOnce())->method('findOneByActiveRequest')->will($this->returnValue($mockDomain));

        $mockSiteRepository = $this->getMockBuilder(SiteRepository::class)->disableOriginalConstructor()->getMock();
        $mockSiteRepository->expects($this->any())->method('findFirstOnline')->will($this->returnValue(null));

        $contentContextFactory = $this->getMockBuilder(ContentContextFactory::class)->setMethods([
            'validateContextProperties',
            'mergeDimensionValues',
            'mergeTargetDimensionContextProperties',
            'getIdentifier'
        ])->disableOriginalConstructor()->getMock();
        $contentContextFactory->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('abc'));

        $this->inject($contentContextFactory, 'domainRepository', $mockDomainRepository);
        $this->inject($contentContextFactory, 'siteRepository', $mockSiteRepository);
        $this->inject($contentContextFactory, 'now', new \DateTime());

        /** @var ContentContext $context */
        $context = $contentContextFactory->create(['workspaceName' => 'user-test']);
        $this->assertEquals($mockSite, $context->getCurrentSite());
        $this->assertEquals($mockDomain, $context->getCurrentDomain());
    }
}
