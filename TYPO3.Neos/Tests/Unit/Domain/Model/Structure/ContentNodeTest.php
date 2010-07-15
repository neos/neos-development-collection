<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Structure;

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
 * Testcase for the "Content Node" domain model
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ContentNodeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentAttachesContentToTheContentNode() {
		$locale = new \F3\FLOW3\I18n\Locale('en-EN');

		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getLocale')->will($this->returnValue($locale));

		$mockContent = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent->expects($this->once())->method('getLocale')->will($this->returnValue($locale));

		$contentNode = new \F3\TYPO3\Domain\Model\Structure\ContentNode();

		$mockContent->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));

		$contentNode->setContent($mockContent);

		$this->assertSame($mockContent, $contentNode->getContent($mockContentContext));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentOverwritesAnyExistingContentMatchingTheSameLanguageAndRegion() {
		$locale1 = new \F3\FLOW3\I18n\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent1->expects($this->once())->method('getLocale')->will($this->returnValue($locale1));

		$locale2 = clone $locale1;
		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent2->expects($this->once())->method('getLocale')->will($this->returnValue($locale2));

		$contentNode = new \F3\TYPO3\Domain\Model\Structure\ContentNode();

		$mockContent1->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));
		$mockContent2->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));

		$contentNode->setContent($mockContent1);
		$contentNode->setContent($mockContent2);

		$locale3 = clone $locale1;
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getLocale')->will($this->returnValue($locale3));

		$this->assertSame($mockContent2, $contentNode->getContent($mockContentContext));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentSetsTheContentNodesContentTypeAccordingly() {
		$locale = new \F3\FLOW3\I18n\Locale('en-EN');
		$mockContent = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent->expects($this->once())->method('getLocale')->will($this->returnValue($locale));

		$contentNode = new \F3\TYPO3\Domain\Model\Structure\ContentNode();
		$mockContent->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));

		$contentNode->setContent($mockContent);

		$this->assertSame(get_class($mockContent), $contentNode->getContentType());
	}

	/**
	 * @test
	 * @expectedException \F3\TYPO3\Domain\Exception\InvalidContentTypeException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentThrowsAnExceptionIfContentIsAddedNotMatchingTheTypeOfExistingContent() {
		$locale = new \F3\FLOW3\I18n\Locale('en-EN');

		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array(), uniqid('SomeContentClassName'));
		$mockContent1->expects($this->once())->method('getLocale')->will($this->returnValue($locale));

		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array(), uniqid('SomeContentClassName'));

		$contentNode = new \F3\TYPO3\Domain\Model\Structure\ContentNode();
		$mockContent1->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));
		$mockContent2->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));

		$contentNode->setContent($mockContent1);
		$contentNode->setContent($mockContent2);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentReturnsNullIfNoContentMatchedTheLocale() {
		$locale1 = new \F3\FLOW3\I18n\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent1->expects($this->once())->method('getLocale')->will($this->returnValue($locale1));

		$locale2 = new \F3\FLOW3\I18n\Locale('de-DE');
		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent2->expects($this->once())->method('getLocale')->will($this->returnValue($locale2));

		$contentNode = new \F3\TYPO3\Domain\Model\Structure\ContentNode();
		$mockContent1->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));
		$mockContent2->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));

		$contentNode->setContent($mockContent1);
		$contentNode->setContent($mockContent2);

		$locale3 = new \F3\FLOW3\I18n\Locale('dk-DK');
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getLocale')->will($this->returnValue($locale3));

		$this->assertNULL($contentNode->getContent($mockContentContext));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeContentDetachesTheGivenContentObjectFromTheContentNode() {
		$locale1 = new \F3\FLOW3\I18n\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent1->expects($this->any())->method('getLocale')->will($this->returnValue($locale1));

		$locale2 = new \F3\FLOW3\I18n\Locale('de-DE');
		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent2->expects($this->any())->method('getLocale')->will($this->returnValue($locale2));

		$mockContentContext1 = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext1->expects($this->any())->method('getLocale')->will($this->returnValue($locale1));

		$mockContentContext2 = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext2->expects($this->any())->method('getLocale')->will($this->returnValue($locale2));

		$contentNode = new \F3\TYPO3\Domain\Model\Structure\ContentNode();
		$mockContent1->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));
		$mockContent2->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));

		$contentNode->setContent($mockContent1);
		$contentNode->setContent($mockContent2);

		$contentNode->removeContent($mockContent2);

		$this->assertSame($mockContent1, $contentNode->getContent($mockContentContext1));
		$this->assertNull($contentNode->getContent($mockContentContext2));
	}

	/**
	 * @test
	 * @expectedException \F3\TYPO3\Domain\Exception\NoSuchContentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeContentThrowsAnExceptionIfTheGivenContentIsNotAttachedToTheContentNode() {
		$locale1 = new \F3\FLOW3\I18n\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent1->expects($this->any())->method('getLocale')->will($this->returnValue($locale1));

		$locale2 = new \F3\FLOW3\I18n\Locale('de-DE');
		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent2->expects($this->any())->method('getLocale')->will($this->returnValue($locale2));

		$contentNode = new \F3\TYPO3\Domain\Model\Structure\ContentNode();
		$mockContent1->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));

		$contentNode->setContent($mockContent1);

		$contentNode->removeContent($mockContent2);
	}

	/**
	 * @test
	 */
	public function removeContentUnsetsTheContentTypeIfTheLastContentObjectIsRemoved() {
		$locale1 = new \F3\FLOW3\I18n\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent1->expects($this->any())->method('getLocale')->will($this->returnValue($locale1));

		$contentNode = new \F3\TYPO3\Domain\Model\Structure\ContentNode();
		$mockContent1->expects($this->once())->method('getContainingNode')->will($this->returnValue($contentNode));

		$contentNode->setContent($mockContent1);

		$contentNode->removeContent($mockContent1);

		$this->assertNull($contentNode->getContentType());

	}
}
?>