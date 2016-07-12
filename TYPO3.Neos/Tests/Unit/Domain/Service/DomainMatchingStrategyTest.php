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

/**
 * Testcase for the Content Service
 *
 */
class DomainMatchingStrategyTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getSortedMatchesReturnsOneGivenDomainIfItMatchesExactly()
    {
        $mockDomains = array($this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->getMock());
        $mockDomains[0]->expects($this->any())->method('getHostPattern')->will($this->returnValue('www.neos.io'));
        $expectedDomains = array($mockDomains[0]);

        $strategy = new \TYPO3\Neos\Domain\Service\DomainMatchingStrategy();
        $actualDomains = $strategy->getSortedMatches('www.neos.io', $mockDomains);
        $this->assertSame($expectedDomains, $actualDomains);
    }

    /**
     * @test
     */
    public function getSortedMatchesFiltersTheGivenDomainsByTheSpecifiedHostAndReturnsThemSortedWithBestMatchesFirst()
    {
        $mockDomains = array(
            $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
            $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
            $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
            $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
        );

        $mockDomains[0]->setHostPattern('*.typo3.org');
        $mockDomains[1]->setHostPattern('flow.typo3.org');
        $mockDomains[2]->setHostPattern('*');
        $mockDomains[3]->setHostPattern('yacumboolu.typo3.org');

        $expectedDomains = array(
            $mockDomains[1],
            $mockDomains[0],
            $mockDomains[2]
        );

        $strategy = new \TYPO3\Neos\Domain\Service\DomainMatchingStrategy();
        $actualDomains = $strategy->getSortedMatches('flow.typo3.org', $mockDomains);
        $this->assertSame($expectedDomains, $actualDomains);
    }

    /**
     * @test
     */
    public function getSortedMatchesReturnsNoMatchIfDomainIsLongerThanHostname()
    {
        $mockDomains = array(
            $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->setMethods(array('dummy'))->getMock(),
        );

        $mockDomains[0]->setHostPattern('flow.typo3.org');

        $expectedDomains = array();

        $strategy = new \TYPO3\Neos\Domain\Service\DomainMatchingStrategy();
        $actualDomains = $strategy->getSortedMatches('typo3.org', $mockDomains);
        $this->assertSame($expectedDomains, $actualDomains);
    }
}
