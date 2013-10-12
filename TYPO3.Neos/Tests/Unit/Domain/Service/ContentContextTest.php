<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Service;

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
 * Testcase for the Content Context
 *
 */
class ContentContextTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Neos\Domain\Service\ContentContextFactory
	 */
	protected $contextFactory;

	public function setUp() {
		$this->contextFactory = new \TYPO3\Neos\Domain\Service\ContentContextFactory();
	}

	/**
	 * @test
	 */
	public function getCurrentSiteReturnsTheCurrentSite() {
		$mockSite = $this->getMock('TYPO3\Neos\Domain\Model\Site', array(), array(), '', FALSE);

		$contextProperties = array(
			'workspaceName' => NULL,
			'currentDateTime' => new \DateTime(),
			'locale' => new \TYPO3\Flow\I18n\Locale('mul_ZZ'),
			'invisibleContentShown' => NULL,
			'removedContentShown' => NULL,
			'inaccessibleContentShown' => NULL,
			'currentSite' => $mockSite,
			'currentDomain' => NULL
		);

		$contentContext = $this->getMock($this->buildAccessibleProxy('TYPO3\Neos\Domain\Service\ContentContext'), array('dummy'), $contextProperties);
		$this->assertSame($mockSite, $contentContext->getCurrentSite());
	}

	/**
	 * @test
	 */
	public function getCurrentDomainReturnsTheCurrentDomainIfAny() {
		$mockDomain = $this->getMock('TYPO3\Neos\Domain\Model\Domain', array(), array(), '', FALSE);


		$contextProperties = array(
			'workspaceName' => NULL,
			'currentDateTime' => new \DateTime(),
			'locale' => new \TYPO3\Flow\I18n\Locale('mul_ZZ'),
			'invisibleContentShown' => NULL,
			'removedContentShown' => NULL,
			'inaccessibleContentShown' => NULL,
			'currentSite' => NULL,
			'currentDomain' => NULL
		);
		$contentContext = $this->getMock($this->buildAccessibleProxy('TYPO3\Neos\Domain\Service\ContentContext'), array('dummy'), $contextProperties);

		$this->assertNull($contentContext->getCurrentDomain());
		$contentContext->_set('currentDomain', $mockDomain);
		$this->assertSame($mockDomain, $contentContext->getCurrentDomain());
	}

}
