<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\ContentContextFactory;

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

    public function setUp()
    {
        $this->contextFactory = new ContentContextFactory();
    }

    /**
     * @test
     */
    public function getCurrentSiteReturnsTheCurrentSite()
    {
        $mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();

        $contextProperties = array(
            'workspaceName' => null,
            'currentDateTime' => new \DateTime(),
            'dimensions' => array(),
            'targetDimensions' => array(),
            'invisibleContentShown' => null,
            'removedContentShown' => null,
            'inaccessibleContentShown' => null,
            'currentSite' => $mockSite,
            'currentDomain' => null
        );

        $contentContext = $this->getAccessibleMock(ContentContext::class, array('dummy'), $contextProperties);
        $this->assertSame($mockSite, $contentContext->getCurrentSite());
    }

    /**
     * @test
     */
    public function getCurrentDomainReturnsTheCurrentDomainIfAny()
    {
        $mockDomain = $this->getMockBuilder(Domain::class)->disableOriginalConstructor()->getMock();

        $contextProperties = array(
            'workspaceName' => null,
            'currentDateTime' => new \DateTime(),
            'dimensions' => array(),
            'targetDimensions' => array(),
            'invisibleContentShown' => null,
            'removedContentShown' => null,
            'inaccessibleContentShown' => null,
            'currentSite' => null,
            'currentDomain' => null
        );
        $contentContext = $this->getAccessibleMock(ContentContext::class, array('dummy'), $contextProperties);

        $this->assertNull($contentContext->getCurrentDomain());
        $contentContext->_set('currentDomain', $mockDomain);
        $this->assertSame($mockDomain, $contentContext->getCurrentDomain());
    }
}
