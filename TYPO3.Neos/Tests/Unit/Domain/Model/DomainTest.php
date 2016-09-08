<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Model;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\Site;

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
        $domain->setHostPattern('typo3.com');
        $this->assertSame('typo3.com', $domain->getHostPattern());
    }

    /**
     * @test
     */
    public function setSiteSetsTheSiteTheDomainIsPointingTo()
    {
        $mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();

        $domain = new Domain;
        $domain->setSite($mockSite);
        $this->assertSame($mockSite, $domain->getSite());
    }
}
