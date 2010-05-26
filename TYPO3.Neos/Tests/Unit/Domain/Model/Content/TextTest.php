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
 * @version $Id: PageTest.php 2817 2009-07-16 14:32:53Z k-fish $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TextTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function textPropertiesCanBeSetAndRetrieved() {
		$text = $this->getMock('F3\TYPO3\Domain\Model\Content\Text', array('dummy'), array(), '', FALSE);
		$text->setHeadLine('On The Flight To Frankfurt');
		$text->setText('Make sure to start watching a movie in time so you can watch the end before landing.');

		$this->assertSame('On The Flight To Frankfurt', $text->getHeadline());
		$this->assertSame('Make sure to start watching a movie in time so you can watch the end before landing.', $text->getText());
  	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function headlineIsUsedAsALabelIfAvailable() {
		$text = $this->getMock('F3\TYPO3\Domain\Model\Content\Text', array('dummy'), array(), '', FALSE);

		$this->assertSame('[Untitled]', $text->getLabel());
		$text->setHeadLine('On The Flight To Frankfurt');
		$this->assertSame('On The Flight To Frankfurt', $text->getLabel());
  	}
}


?>