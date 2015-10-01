<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Context
 *
 */
class ContextTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactory
     */
    protected $contextFactory;

    public function setUp()
    {
        $this->contextFactory = new \TYPO3\TYPO3CR\Domain\Service\ContextFactory();
        $this->inject($this->contextFactory, 'now', new \TYPO3\Flow\Utility\Now());
        $this->inject($this->contextFactory, 'securityContext', $this->getMock('TYPO3\Flow\Security\Context'));

        $mockContentDimensionRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
        $mockContentDimensionRepository->expects($this->any())->method('findAll')->will($this->returnValue(array()));
        $this->inject($this->contextFactory, 'contentDimensionRepository', $mockContentDimensionRepository);
    }

    /**
     * @test
     */
    public function getCurrentDateTimeReturnsACurrentDateAndTime()
    {
        $now = new \TYPO3\Flow\Utility\Now();

        $context = $this->contextFactory->create(array());

        $currentTime = $context->getCurrentDateTime();
        $this->assertInstanceOf('\DateTime', $currentTime);
        $this->assertEquals($now->getTimestamp(), $currentTime->getTimestamp(), 1);
    }

    /**
     * @test
     */
    public function setDateTimeAllowsForMockingTheCurrentTime()
    {
        $simulatedCurrentTime = new \DateTime();
        date_add($simulatedCurrentTime, new \DateInterval('P1D'));

        $context = $this->contextFactory->create(array('currentDateTime' => $simulatedCurrentTime));

        $this->assertEquals($simulatedCurrentTime, $context->getCurrentDateTime());
    }
}
