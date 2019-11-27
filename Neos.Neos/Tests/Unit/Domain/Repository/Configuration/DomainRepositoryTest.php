<?php
namespace Neos\Neos\Tests\Unit\Domain\Repository\Configuration;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Service\DomainMatchingStrategy;

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
        $mockDomains = [];
        $mockDomains[] = $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock();
        $mockDomains[] = $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock();
        $mockDomains[] = $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock();

        $expectedDomains = [$mockDomains[0], $mockDomains[2]];

        $mockDomainMatchingStrategy = $this->getMockBuilder(DomainMatchingStrategy::class)->disableOriginalConstructor()->getMock();
        $mockDomainMatchingStrategy->expects(self::any())->method('getSortedMatches')->with('myhost', $mockDomains)->will(self::returnValue($expectedDomains));

        $mockResult = $this->createMock(QueryResultInterface::class);
        $mockResult->expects(self::once())->method('toArray')->will(self::returnValue($mockDomains));
        $domainRepository = $this->getAccessibleMock(DomainRepository::class, ['findAll'], [], '', false);
        $domainRepository->expects(self::once())->method('findAll')->will(self::returnValue($mockResult));
        $domainRepository->_set('domainMatchingStrategy', $mockDomainMatchingStrategy);

        $actualDomains = $domainRepository->findByHost('myhost');
        self::assertSame($expectedDomains, $actualDomains);
    }
}
