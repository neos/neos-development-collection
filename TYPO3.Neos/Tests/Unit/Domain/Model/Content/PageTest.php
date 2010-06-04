<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Content;

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
 * Testcase for the domain model of a Page
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aPageCanBeHiddenAndUnhidden() {
		$page = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array('dummy'), array(), '', FALSE);
		$page->hide();
		$this->assertTrue($page->isHidden());
		$page->unhide();
		$this->assertFalse($page->isHidden());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theStartAndEndTimeForTheVisibilityOfAPageCanBeSetAndRetrieved() {
		$startTime = new \DateTime('24.05.2010 18:50');
		$endTime = new \DateTime('24.05.2010 18:52');

		$page = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array('dummy'), array(), '', FALSE);
		$page->setStartTime($startTime);
		$this->assertEquals($startTime, $page->getStartTime());
		$this->assertNotSame($startTime, $page->getStartTime());
		$page->setEndTime($endTime);
		$this->assertEquals($endTime, $page->getEndTime());
		$this->assertNotSame($endTime, $page->getEndTime());
	}
	
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function theStartAndEndTimeForTheVisibilityOfAPageAreNullByDefault() {
		$page = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array('dummy'), array(), '', FALSE);
		$this->assertNull($page->getStartTime());
		$this->assertNull($page->getEndTime());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theTitleIsUsedAsTheLabel() {
		$page = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array('dummy'), array(), '', FALSE);
		$page->setTitle('El título');
		$this->assertSame('El título', $page->getLabel());
		$this->assertSame('El título', $page->getTitle());
	}

}


?>