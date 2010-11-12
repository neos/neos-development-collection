<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Tests\Unit\Domain\Repository\Configuration;

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
 * Testcase for the Domain Repository
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DomainRepositoryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findByHostInvokesTheDomainMatchingStrategyToFindDomainsMatchingTheGivenHost() {
		$mockDomains = array();
		$mockDomains[] = $this->getMock('F3\TYPO3\Domain\Model\Domain', array(), array(), '', FALSE);
		$mockDomains[] = $this->getMock('F3\TYPO3\Domain\Model\Domain', array(), array(), '', FALSE);
		$mockDomains[] = $this->getMock('F3\TYPO3\Domain\Model\Domain', array(), array(), '', FALSE);

		$expectedDomains = array($mockDomains[0], $mockDomains[2]);

		$mockDomainMatchingStrategy = $this->getMock('F3\TYPO3\Domain\Service\DomainMatchingStrategy', array(), array(), '', FALSE);
		$mockDomainMatchingStrategy->expects($this->any())->method('getSortedMatches')->with('myhost', $mockDomains)->will($this->returnValue($expectedDomains));

		$mockResult = $this->getMock('F3\FLOW3\Persistence\QueryResultInterface');
		$mockResult->expects($this->once())->method('toArray')->will($this->returnValue($mockDomains));
		$domainRepository = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Repository\DomainRepository'), array('findAll'), array(), '', FALSE);
		$domainRepository->expects($this->once())->method('findAll')->will($this->returnValue($mockResult));
		$domainRepository->_set('domainMatchingStrategy', $mockDomainMatchingStrategy);

		$actualDomains = $domainRepository->findByHost('myhost');
		$this->assertSame($expectedDomains, $actualDomains);
	}

}

?>