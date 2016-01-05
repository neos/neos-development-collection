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

/**
 * Testcase for the "Domain" domain model
 *
 */
class DomainTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function setHostPatternAllowsForSettingTheHostPatternOfTheDomain()
    {
        $domain = new \TYPO3\Neos\Domain\Model\Domain();
        $domain->setHostPattern('typo3.com');
        $this->assertSame('typo3.com', $domain->getHostPattern());
    }

    /**
     * @test
     */
    public function setSiteSetsTheSiteTheDomainIsPointingTo()
    {
        $mockSite = $this->getMock('TYPO3\Neos\Domain\Model\Site', array(), array(), '', false);

        $domain = new \TYPO3\Neos\Domain\Model\Domain;
        $domain->setSite($mockSite);
        $this->assertSame($mockSite, $domain->getSite());
    }
}
