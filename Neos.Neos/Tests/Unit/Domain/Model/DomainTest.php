<?php
namespace Neos\Neos\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;

/**
 * Testcase for the "Domain" domain model
 *
 */
class DomainTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setHostPatternAllowsForSettingTheHostPatternOfTheDomain()
    {
        $domain = new Domain();
        $domain->setHostname('neos.io');
        self::assertSame('neos.io', $domain->getHostname());
    }

    /**
     * @test
     */
    public function setSiteSetsTheSiteTheDomainIsPointingTo()
    {
        /** @var Site $mockSite */
        $mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();

        $domain = new Domain;
        $domain->setSite($mockSite);
        self::assertSame($mockSite, $domain->getSite());
    }
}
