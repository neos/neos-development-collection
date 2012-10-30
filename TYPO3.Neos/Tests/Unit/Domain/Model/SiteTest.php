<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Model;

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
 * Testcase for the "Site" domain model
 *
 */
class SiteTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function aNameCanBeSetAndRetrievedFromTheSite() {
		$site = new \TYPO3\Neos\Domain\Model\Site('');
		$site->setName('My cool website');
		$this->assertSame('My cool website', $site->getName());
	}

	/**
	 * @test
	 */
	public function theDefaultStateOfASiteIsOnline() {
		$site = new \TYPO3\Neos\Domain\Model\Site('');
		$this->assertSame(\TYPO3\Neos\Domain\Model\Site::STATE_ONLINE, $site->getState());
	}

	/**
	 * @test
	 */
	public function theStateCanBeSetAndRetrieved() {
		$site = new \TYPO3\Neos\Domain\Model\Site('');
		$site->setState(\TYPO3\Neos\Domain\Model\Site::STATE_OFFLINE);
		$this->assertSame(\TYPO3\Neos\Domain\Model\Site::STATE_OFFLINE, $site->getState());
	}

	/**
	 * @test
	 */
	public function theSiteResourcesPackageKeyCanBeSetAndRetrieved() {
		$site = new \TYPO3\Neos\Domain\Model\Site('');
		$site->setSiteResourcesPackageKey('Foo');
		$this->assertSame('Foo', $site->getSiteResourcesPackageKey());
	}

}

?>