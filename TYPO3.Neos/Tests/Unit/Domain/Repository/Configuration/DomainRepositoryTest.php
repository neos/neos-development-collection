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
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Service\DomainMatchingStrategy;

/**
 * Testcase for the Domain Repository
 *
 */
class DomainRepositoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function findByHostInvokesTheDomainMatchingStrategyToFindDomainsMatchingTheGivenHost()
    {
        $mockDomains = array();
        $mockDomains[] = $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock();
        $mockDomains[] = $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock();
        $mockDomains[] = $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock();

        $expectedDomains = array($mockDomains[0], $mockDomains[2]);

        $mockDomainMatchingStrategy = $this->getMockBuilder(DomainMatchingStrategy::class)->disableOriginalConstructor()->getMock();
        $mockDomainMatchingStrategy->expects($this->any())->method('getSortedMatches')->with('myhost', $mockDomains)->will($this->returnValue($expectedDomains));

        $mockResult = $this->createMock(QueryResultInterface::class);
        $mockResult->expects($this->once())->method('toArray')->will($this->returnValue($mockDomains));
        $domainRepository = $this->getAccessibleMock(DomainRepository::class, array('findAll'), array(), '', false);
        $domainRepository->expects($this->once())->method('findAll')->will($this->returnValue($mockResult));
        $domainRepository->_set('domainMatchingStrategy', $mockDomainMatchingStrategy);

        $actualDomains = $domainRepository->findByHost('myhost');
        $this->assertSame($expectedDomains, $actualDomains);
    }
}
