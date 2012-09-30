<?php
namespace TYPO3\TYPO3\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Content Service
 *
 */
class DomainMatchingStrategyTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getSortedMatchesReturnsOneGivenDomainIfItMatchesExactly() {
		$mockDomains = array($this->getMock('TYPO3\TYPO3\Domain\Model\Domain', array(), array(), '', FALSE));
		$mockDomains[0]->expects($this->any())->method('getHostPattern')->will($this->returnValue('www.typo3.org'));
		$expectedDomains = array($mockDomains[0]);

		$strategy = new \TYPO3\TYPO3\Domain\Service\DomainMatchingStrategy();
		$actualDomains = $strategy->getSortedMatches('www.typo3.org', $mockDomains);
		$this->assertSame($expectedDomains, $actualDomains);
	}

	/**
	 * @test
	 */
	public function getSortedMatchesFiltersTheGivenDomainsByTheSpecifiedHostAndReturnsThemSortedWithBestMatchesFirst() {
		$mockDomains = array(
			$this->getMock('TYPO3\TYPO3\Domain\Model\Domain', array('dummy'), array(), '', FALSE),
			$this->getMock('TYPO3\TYPO3\Domain\Model\Domain', array('dummy'), array(), '', FALSE),
			$this->getMock('TYPO3\TYPO3\Domain\Model\Domain', array('dummy'), array(), '', FALSE),
			$this->getMock('TYPO3\TYPO3\Domain\Model\Domain', array('dummy'), array(), '', FALSE),
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

		$strategy = new \TYPO3\TYPO3\Domain\Service\DomainMatchingStrategy();
		$actualDomains = $strategy->getSortedMatches('flow.typo3.org', $mockDomains);
		$this->assertSame($expectedDomains, $actualDomains);
	}
}
?>