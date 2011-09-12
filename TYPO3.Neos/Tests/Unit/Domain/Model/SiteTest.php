<?php
namespace TYPO3\TYPO3\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the "Site" domain model
 *
 */
class SiteTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aNameCanBeSetAndRetrievedFromTheSite() {
		$site = new \TYPO3\TYPO3\Domain\Model\Site('');
		$site->setName('My cool website');
		$this->assertSame('My cool website', $site->getName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDefaultStateOfASiteIsOnline() {
		$site = new \TYPO3\TYPO3\Domain\Model\Site('');
		$this->assertSame(\TYPO3\TYPO3\Domain\Model\Site::STATE_ONLINE, $site->getState());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theStateCanBeSetAndRetrieved() {
		$site = new \TYPO3\TYPO3\Domain\Model\Site('');
		$site->setState(\TYPO3\TYPO3\Domain\Model\Site::STATE_OFFLINE);
		$this->assertSame(\TYPO3\TYPO3\Domain\Model\Site::STATE_OFFLINE, $site->getState());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theSiteResourcesPackageKeyCanBeSetAndRetrieved() {
		$site = new \TYPO3\TYPO3\Domain\Model\Site('');
		$site->setSiteResourcesPackageKey('Foo');
		$this->assertSame('Foo', $site->getSiteResourcesPackageKey());
	}

}

?>