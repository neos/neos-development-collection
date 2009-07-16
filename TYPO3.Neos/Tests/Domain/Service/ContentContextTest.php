<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Service;

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
 * Testcase for the Content Context
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ContentContextTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentServiceReturnsTheContentServiceBoundToTheContext() {
		$mockContentService = $this->getMock('F3\TYPO3\Domain\Service\ContentService', array(), array(), '', FALSE);

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));
		$contentContext->_set('contentService', $mockContentService);
		$this->assertSame($mockContentService, $contentContext->getContentService());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentDateTimeReturnsACurrentDateAndTime() {
		$almostCurrentTime = new \DateTime();
		date_sub($almostCurrentTime, new \DateInterval('P0DT1S'));

		$contentContext = new \F3\TYPO3\Domain\Service\ContentContext();
		$currentTime = $contentContext->getCurrentDateTime();
		$this->assertTrue($almostCurrentTime < $currentTime);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDateTimeAllowsForMockingTheCurrentTime() {
		$simulatedCurrentTime = new \DateTime();
		date_add($simulatedCurrentTime, new \DateInterval('P1D'));

		$contentContext = new \F3\TYPO3\Domain\Service\ContentContext();
		$contentContext->setCurrentDateTime($simulatedCurrentTime);

		$this->assertEquals($simulatedCurrentTime, $contentContext->getCurrentDateTime());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLocaleReturnsByDefaultAnInternationalMultilingualLocale() {
		$locale = new \F3\FLOW3\Locale\Locale('mul-ZZ');

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array('create'), array(), '', FALSE);
		$mockObjectFactory->expects($this->at(1))->method('create')->with('F3\FLOW3\Locale\Locale', 'mul-ZZ')->will($this->returnValue($locale));

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));
		$contentContext->_set('objectFactory', $mockObjectFactory);
		$contentContext->initializeObject();

		$this->assertSame($locale, $contentContext->getLocale());
	}
}


?>