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
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
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

    public function setUp()
    {
        $this->contextFactory = new ContextFactory();
        $this->inject($this->contextFactory, 'now', new \Neos\Flow\Utility\Now());
        $this->inject($this->contextFactory, 'securityContext', $this->createMock(Context::class));

        $mockContentDimensionSource = $this->createMock(Dimension\ContentDimensionSourceInterface::class);
        $mockContentDimensionSource->expects($this->any())->method('getContentDimensionsOrderedByPriority')->will($this->returnValue([]));
        $this->inject($this->contextFactory, 'contentDimensionSource', $mockContentDimensionSource);
    }

    /**
     * @test
     */
    public function getCurrentDateTimeReturnsACurrentDateAndTime()
    {
        $now = new \DateTime();

        $context = $this->contextFactory->create(array());

        $currentTime = $context->getCurrentDateTime();
        $this->assertInstanceOf('\DateTimeInterface', $currentTime);
        $this->assertEquals($now->getTimestamp(), $currentTime->getTimestamp(), 1);
    }

    /**
     * @test
     */
    public function setDateTimeAllowsForMockingTheCurrentTime()
    {
        $simulatedCurrentTime = new \DateTime();
        $simulatedCurrentTime->add(new \DateInterval('P1D'));

        $context = $this->contextFactory->create(array('currentDateTime' => $simulatedCurrentTime));

        $this->assertEquals($simulatedCurrentTime, $context->getCurrentDateTime());
    }
}
