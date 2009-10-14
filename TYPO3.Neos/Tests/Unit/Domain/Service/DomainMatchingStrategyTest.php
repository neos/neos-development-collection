<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Content Service
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DomainMatchingStrategyTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSortedMatchesReturnsOneGivenDomainIfItMatchesExactly() {
		$mockDomains = array($this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array(), array(), '', FALSE));
		$mockDomains[0]->expects($this->any())->method('getHostPattern')->will($this->returnValue('www.typo3.org'));
		$expectedDomains = array($mockDomains[0]);

		$strategy = new \F3\TYPO3\Domain\Service\DomainMatchingStrategy();
		$actualDomains = $strategy->getSortedMatches('www.typo3.org', $mockDomains);
		$this->assertSame($expectedDomains, $actualDomains);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSortedMatchesFiltersTheGivenDomainsByTheSpecifiedHostAndReturnsThemSortedWithBestMatchesFirst() {
		$mockDomains = array(
			$this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array('dummy'), array(), '', FALSE),
			$this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array('dummy'), array(), '', FALSE),
			$this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array('dummy'), array(), '', FALSE),
			$this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array('dummy'), array(), '', FALSE),
		);

		$mockDomains[0]->setHostPattern('*.typo3.org');
		$mockDomains[1]->setHostPattern('flow3.typo3.org');
		$mockDomains[2]->setHostPattern('*');
		$mockDomains[3]->setHostPattern('yacumboolu.typo3.org');

		$expectedDomains = array(
			$mockDomains[1],
			$mockDomains[0],
			$mockDomains[2]
		);

		$strategy = new \F3\TYPO3\Domain\Service\DomainMatchingStrategy();
		$actualDomains = $strategy->getSortedMatches('flow3.typo3.org', $mockDomains);
		$this->assertSame($expectedDomains, $actualDomains);
	}
}
?>