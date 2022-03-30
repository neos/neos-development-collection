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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Service\DomainMatchingStrategy;

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
        $mockDomains = [$this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock()];
        $mockDomains[0]->expects(self::any())->method('getHostname')->will(self::returnValue('www.neos.io'));
        $expectedDomains = [$mockDomains[0]];

        $strategy = new DomainMatchingStrategy();
        $actualDomains = $strategy->getSortedMatches('www.neos.io', $mockDomains);
        self::assertSame($expectedDomains, $actualDomains);
    }

    /**
     * @test
     */
    public function getSortedMatchesFiltersTheGivenDomainsByTheSpecifiedHostAndReturnsThemSortedWithBestMatchesFirst()
    {
        $mockDomains = [
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(['dummy'])->getMock(),
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(['dummy'])->getMock(),
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(['dummy'])->getMock(),
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(['dummy'])->getMock(),
        ];

        $mockDomains[0]->setHostname('neos.io');
        $mockDomains[1]->setHostname('flow.neos.io');
        $mockDomains[2]->setHostname('');
        $mockDomains[3]->setHostname('yacumboolu.neos.io');

        $expectedDomains = [
            $mockDomains[1],
            $mockDomains[0]
        ];

        $strategy = new DomainMatchingStrategy();
        $actualDomains = $strategy->getSortedMatches('flow.neos.io', $mockDomains);
        self::assertSame($expectedDomains, $actualDomains);
    }

    /**
     * @test
     */
    public function getSortedMatchesReturnsNoMatchIfDomainIsLongerThanHostname()
    {
        $mockDomains = [
            $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->setMethods(['dummy'])->getMock(),
        ];

        $mockDomains[0]->setHostname('flow.neos.io');

        $expectedDomains = [];

        $strategy = new DomainMatchingStrategy();
        $actualDomains = $strategy->getSortedMatches('neos.io', $mockDomains);
        self::assertSame($expectedDomains, $actualDomains);
    }
}
