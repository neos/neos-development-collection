<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Repository\Configuration;

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
 * Testcase for the Domain Repository
 *
 */
class DomainRepositoryTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function findByHostInvokesTheDomainMatchingStrategyToFindDomainsMatchingTheGivenHost()
    {
        $mockDomains = array();
        $mockDomains[] = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->getMock();
        $mockDomains[] = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->getMock();
        $mockDomains[] = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->getMock();

        $expectedDomains = array($mockDomains[0], $mockDomains[2]);

        $mockDomainMatchingStrategy = $this->getMockBuilder('TYPO3\Neos\Domain\Service\DomainMatchingStrategy')->disableOriginalConstructor()->getMock();
        $mockDomainMatchingStrategy->expects($this->any())->method('getSortedMatches')->with('myhost', $mockDomains)->will($this->returnValue($expectedDomains));

        $mockResult = $this->createMock('TYPO3\Flow\Persistence\QueryResultInterface');
        $mockResult->expects($this->once())->method('toArray')->will($this->returnValue($mockDomains));
        $domainRepository = $this->getAccessibleMock('TYPO3\Neos\Domain\Repository\DomainRepository', array('findAll'), array(), '', false);
        $domainRepository->expects($this->once())->method('findAll')->will($this->returnValue($mockResult));
        $domainRepository->_set('domainMatchingStrategy', $mockDomainMatchingStrategy);

        $actualDomains = $domainRepository->findByHost('myhost');
        $this->assertSame($expectedDomains, $actualDomains);
    }
}
