<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Service;

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
use TYPO3\Neos\Domain\Service\DomainMatchingStrategy;

/**
 * Testcase for the Content Service
 *
 */
class DomainMatchingStrategyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getSortedMatchesReturnsOneGivenDomainIfItMatchesExactly()
    {
        $mockDomains = array($this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock());
        $mockDomains[0]->expects($this->any())->method('getHostname')->will($this->returnValue('www.neos.io'));
        $expectedDomains = array($mockDomains[0]);

        $strategy = new DomainMatchingStrategy();
        $actualDomains = $strategy->getSortedMatches('www.neos.io', $mockDomains);
        $this->assertSame($expectedDomains, $actualDomains);
    }

    /**
     * @test
     */
    public function getSortedMatchesFiltersTheGivenDomainsByTheSpecifiedHostAndReturnsThemSortedWithBestMatchesFirst()
    {
        $mockDomains = array(
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
        );

        $mockDomains[0]->setHostname('neos.io');
        $mockDomains[1]->setHostname('flow.neos.io');
        $mockDomains[3]->setHostname('yacumboolu.neos.io');

        $expectedDomains = array(
            $mockDomains[1],
            $mockDomains[0]
        );

        $strategy = new DomainMatchingStrategy();
        $actualDomains = $strategy->getSortedMatches('flow.neos.io', $mockDomains);
        $this->assertSame($expectedDomains, $actualDomains);
    }

    /**
     * @test
     */
    public function getSortedMatchesReturnsNoMatchIfDomainIsLongerThanHostname()
    {
        $mockDomains = array(
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
        );

        $mockDomains[0]->setHostname('flow.neos.io');

        $expectedDomains = array();

        $strategy = new DomainMatchingStrategy();
        $actualDomains = $strategy->getSortedMatches('neos.io', $mockDomains);
        $this->assertSame($expectedDomains, $actualDomains);
    }
}
