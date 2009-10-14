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
 * Testcase for the Content Service
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ContentServiceTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function contentServiceIsBoundToASpecificContentContext() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);

		$contentService = new \F3\TYPO3\Domain\Service\ContentService($mockContentContext);
		$this->assertSame($mockContentContext, $contentService->getContentContext());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createInsideCreatesContentAndAContentNodeInsideTheSpecifiedExistingContent() {
		$locale = new \F3\FLOW3\Locale\Locale('de-DE');

		$mockExistingNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode', array(), array(), '', FALSE);

		$mockExistingContent = $this->getMock('F3\TYPO3\Domain\Model\Content\AbstractContent', array('getNode'), array(), '', FALSE);
		$mockExistingContent->expects($this->once())->method('getNode')->will($this->returnValue($mockExistingNode));

		$mockNewContent = $this->getMock('F3\TYPO3\Domain\Model\Content\AbstractContent', array(), array(), '', FALSE);
		$mockNewNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode', array(), array(), '', FALSE);
		$mockNewNode->expects($this->once())->method('setNodeName')->with('NewNodeName');

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array(), array(), '', FALSE);
		$mockObjectFactory->expects($this->at(0))->method('create')->with('F3\TYPO3\Domain\Model\Structure\ContentNode')->will($this->returnValue($mockNewNode));
		$mockObjectFactory->expects($this->at(1))->method('create')->with(get_class($mockNewContent), $locale)->will($this->returnValue($mockNewContent));

		$mockExistingNode->expects($this->once())->method('addChildNode')->with($mockNewNode, $locale);

		$mockNewContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockNewContentContext->expects($this->any())->method('getLocale')->will($this->returnValue($locale));

		$contentService = new \F3\TYPO3\Domain\Service\ContentService($mockNewContentContext);
		$contentService->injectObjectFactory($mockObjectFactory);

		$actualContent = $contentService->createInside('NewNodeName', get_class($mockNewContent), $mockExistingContent);
		$this->assertSame($mockNewContent, $actualContent);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createInsideCanCreateContentInsideTheSpecifiedSite() {
		$locale = new \F3\FLOW3\Locale\Locale('de-DE');

		$mockNewContent = $this->getMock('F3\TYPO3\Domain\Model\Content\AbstractContent', array(), array(), '', FALSE);
		$mockNewNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array(), array(), '', FALSE);
		$mockObjectFactory->expects($this->at(0))->method('create')->with('F3\TYPO3\Domain\Model\Structure\ContentNode')->will($this->returnValue($mockNewNode));
		$mockObjectFactory->expects($this->at(1))->method('create')->with(get_class($mockNewContent), $locale)->will($this->returnValue($mockNewContent));

		$mockNewContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockNewContentContext->expects($this->any())->method('getLocale')->will($this->returnValue($locale));

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);
		$mockSite->expects($this->once())->method('addChildNode')->with($mockNewNode, $locale);

		$contentService = new \F3\TYPO3\Domain\Service\ContentService($mockNewContentContext);
		$contentService->injectObjectFactory($mockObjectFactory);

		$actualContent = $contentService->createInside('foo', get_class($mockNewContent), $mockSite);
		$this->assertSame($mockNewContent, $actualContent);
	}

	/**
	 * @test
	 * @expectedException F3\TYPO3\Domain\Exception\InvalidReference
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createInsideThrowsAnExceptionOnInvalidReference() {
		$mockNewContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array(), array(), '', FALSE);

		$contentService = new \F3\TYPO3\Domain\Service\ContentService($mockNewContentContext);
		$contentService->injectObjectFactory($mockObjectFactory);
		$contentService->createInside('foo', get_class($this), 'bar');
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createAfterCreatesContentAndAContentNodeAfterTheSpecifiedExistingContent() {
		$locale = new \F3\FLOW3\Locale\Locale('de-DE');

		$mockExistingParentNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode', array(), array(), '', FALSE);

		$mockExistingNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode', array(), array(), '', FALSE);
		$mockExistingNode->expects($this->once())->method('getParentNode')->will($this->returnValue($mockExistingParentNode));

		$mockExistingContent = $this->getMock('F3\TYPO3\Domain\Model\Content\AbstractContent', array(), array(), '', FALSE);
		$mockExistingContent->expects($this->any())->method('getNode')->will($this->returnValue($mockExistingNode));


		$mockNewContent = $this->getMock('F3\TYPO3\Domain\Model\Content\AbstractContent', array(), array(), '', FALSE);
		$mockNewNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode', array(), array(), '', FALSE);
		$mockNewNode->expects($this->once())->method('setNodeName')->with('NewNodeName');

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array(), array(), '', FALSE);
		$mockObjectFactory->expects($this->at(0))->method('create')->with(get_class($mockNewContent), $locale)->will($this->returnValue($mockNewContent));
		$mockObjectFactory->expects($this->at(1))->method('create')->with('F3\TYPO3\Domain\Model\Structure\ContentNode')->will($this->returnValue($mockNewNode));

		$mockNewNode->expects($this->once())->method('setContent')->with($mockNewContent);
		$mockExistingParentNode->expects($this->once())->method('addChildNodeAfter')->with($mockNewNode, $mockExistingNode);

		$mockNewContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockNewContentContext->expects($this->any())->method('getLocale')->will($this->returnValue($locale));

		$contentService = new \F3\TYPO3\Domain\Service\ContentService($mockNewContentContext);
		$contentService->injectObjectFactory($mockObjectFactory);

		$actualContent = $contentService->createAfter('NewNodeName', get_class($mockNewContent), $mockExistingContent);
		$this->assertSame($mockNewContent, $actualContent);
	}

	/**
	 * @test
	 * @expectedException F3\TYPO3\Domain\Exception\InvalidReference
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createAfterThrowsAnExceptionOnInvalidReference() {
		$mockNewContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array(), array(), '', FALSE);

		$contentService = new \F3\TYPO3\Domain\Service\ContentService($mockNewContentContext);
		$contentService->injectObjectFactory($mockObjectFactory);
		$contentService->createAfter('foo', get_class($this), 'bar');
	}

}

?>