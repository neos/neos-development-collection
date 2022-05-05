<?php
namespace Neos\Neos\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;

/**
 * Testcase for the Content Context
 *
 */
class ContentContextTest extends UnitTestCase
{
    /**
     * @var ContentContextFactory
     */
    protected $contextFactory;

    public function setUp(): void
    {
        $this->contextFactory = new ContentContextFactory();
    }

    /**
     * @test
     */
    public function getCurrentSiteReturnsTheCurrentSite()
    {
        $mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();

        $contextProperties = [
            'workspaceName' => null,
            'currentDateTime' => new \DateTime(),
            'dimensions' => [],
            'targetDimensions' => [],
            'invisibleContentShown' => null,
            'removedContentShown' => null,
            'inaccessibleContentShown' => null,
            'currentSite' => $mockSite,
            'currentDomain' => null
        ];

        $contentContext = $this->getAccessibleMock(ContentContext::class, ['dummy'], $contextProperties);
        self::assertSame($mockSite, $contentContext->getCurrentSite());
    }

    /**
     * @test
     */
    public function getCurrentDomainReturnsTheCurrentDomainIfAny()
    {
        $mockDomain = $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock();

        $contextProperties = [
            'workspaceName' => null,
            'currentDateTime' => new \DateTime(),
            'dimensions' => [],
            'targetDimensions' => [],
            'invisibleContentShown' => null,
            'removedContentShown' => null,
            'inaccessibleContentShown' => null,
            'currentSite' => null,
            'currentDomain' => null
        ];
        $contentContext = $this->getAccessibleMock(ContentContext::class, ['dummy'], $contextProperties);

        self::assertNull($contentContext->getCurrentDomain());
        $contentContext->_set('currentDomain', $mockDomain);
        self::assertSame($mockDomain, $contentContext->getCurrentDomain());
    }
}
