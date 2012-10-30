<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Repository\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Domain Repository
 *
 */
class DomainRepositoryTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function findByHostInvokesTheDomainMatchingStrategyToFindDomainsMatchingTheGivenHost() {
		$mockDomains = array();
		$mockDomains[] = $this->getMock('TYPO3\Neos\Domain\Model\Domain', array(), array(), '', FALSE);
		$mockDomains[] = $this->getMock('TYPO3\Neos\Domain\Model\Domain', array(), array(), '', FALSE);
		$mockDomains[] = $this->getMock('TYPO3\Neos\Domain\Model\Domain', array(), array(), '', FALSE);

		$expectedDomains = array($mockDomains[0], $mockDomains[2]);

		$mockDomainMatchingStrategy = $this->getMock('TYPO3\Neos\Domain\Service\DomainMatchingStrategy', array(), array(), '', FALSE);
		$mockDomainMatchingStrategy->expects($this->any())->method('getSortedMatches')->with('myhost', $mockDomains)->will($this->returnValue($expectedDomains));

		$mockResult = $this->getMock('TYPO3\Flow\Persistence\QueryResultInterface');
		$mockResult->expects($this->once())->method('toArray')->will($this->returnValue($mockDomains));
		$domainRepository = $this->getMock($this->buildAccessibleProxy('TYPO3\Neos\Domain\Repository\DomainRepository'), array('findAll'), array(), '', FALSE);
		$domainRepository->expects($this->once())->method('findAll')->will($this->returnValue($mockResult));
		$domainRepository->_set('domainMatchingStrategy', $mockDomainMatchingStrategy);

		$actualDomains = $domainRepository->findByHost('myhost');
		$this->assertSame($expectedDomains, $actualDomains);
	}

}

?>