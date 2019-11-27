<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Domain\Service\ContextFactory;

/**
 * Testcase for the Context
 *
 */
class ContextTest extends UnitTestCase
{
    /**
     * @var ContextFactory
     */
    protected $contextFactory;

    public function setUp(): void
    {
        $this->contextFactory = new ContextFactory();
        $this->inject($this->contextFactory, 'now', new \Neos\Flow\Utility\Now());
        $this->inject($this->contextFactory, 'securityContext', $this->createMock(Context::class));

        $mockContentDimensionRepository = $this->createMock(ContentDimensionRepository::class);
        $mockContentDimensionRepository->expects(self::any())->method('findAll')->will(self::returnValue([]));
        $this->inject($this->contextFactory, 'contentDimensionRepository', $mockContentDimensionRepository);
    }

    /**
     * @test
     */
    public function getCurrentDateTimeReturnsACurrentDateAndTime()
    {
        $now = new \DateTime();

        $context = $this->contextFactory->create([]);

        $currentTime = $context->getCurrentDateTime();
        self::assertInstanceOf('\DateTimeInterface', $currentTime);
        self::assertEquals($now->getTimestamp(), $currentTime->getTimestamp(), 1);
    }

    /**
     * @test
     */
    public function setDateTimeAllowsForMockingTheCurrentTime()
    {
        $simulatedCurrentTime = new \DateTime();
        $simulatedCurrentTime->add(new \DateInterval('P1D'));

        $context = $this->contextFactory->create(['currentDateTime' => $simulatedCurrentTime]);

        self::assertEquals($simulatedCurrentTime, $context->getCurrentDateTime());
    }
}
