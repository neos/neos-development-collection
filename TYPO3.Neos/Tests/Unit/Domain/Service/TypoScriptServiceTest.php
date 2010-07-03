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

require_once('vfs/vfsStream.php');

/**
 * Testcase for the TypoScript Service
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TypoScriptServiceTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function typoScriptServiceIsBoundToASpecificContentContext() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);

		$typoScriptService = new \F3\TYPO3\Domain\Service\TypoScriptService($mockContentContext);
		$this->assertSame($mockContentContext, $typoScriptService->getContentContext());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getMergedTypoScriptObjectTreeReturnsNullIfPathDoesNotPointToANode() {
		$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array(), array(), '', FALSE);
		$mockNodeService->expects($this->once())->method('getNodesOnPath')->will($this->returnValue(NULL));
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->once())->method('getNodeService')->will($this->returnValue($mockNodeService));

		$typoScriptService = new \F3\TYPO3\Domain\Service\TypoScriptService($mockContentContext);
		$this->assertNull($typoScriptService->getMergedTypoScriptObjectTree('/foobar'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getMergedTypoScriptObjectTreeIteratesOverNodesOnPathAndCollectsTypoScriptFiles() {
		$mockFooNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$mockFooNode->expects($this->any())->method('getNodeName')->will($this->returnValue('foo'));
		$mockFooNode->expects($this->any())->method('getConfigurations')->will($this->returnValue(array()));
		$mockBarNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$mockBarNode->expects($this->any())->method('getNodeName')->will($this->returnValue('bar'));
		$mockBarNode->expects($this->any())->method('getConfigurations')->will($this->returnValue(array()));

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site');
		$mockSite->expects($this->any())->method('getSiteResourcesPackageKey')->will($this->returnValue('SitePackage'));
		$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array(), array(), '', FALSE);
		$mockNodeService->expects($this->any())->method('getNodesOnPath')->will($this->returnValue(array($mockFooNode, $mockBarNode)));
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getNodeService')->will($this->returnValue($mockNodeService));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$typoScriptService = $this->getAccessibleMock('F3\TYPO3\Domain\Service\TypoScriptService', array('readExternalTypoScriptFiles'), array($mockContentContext));
		$typoScriptService->_set('typoScriptParser', $this->getMock('F3\TypoScript\Parser', array(), array(), '', FALSE));
		$typoScriptService->expects($this->at(1))->method('readExternalTypoScriptFiles')->with('resource://SitePackage/Private/TypoScripts/foo/');
		$typoScriptService->expects($this->at(2))->method('readExternalTypoScriptFiles')->with('resource://SitePackage/Private/TypoScripts/foo/bar/');
		$typoScriptService->getMergedTypoScriptObjectTree('/foo/bar');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getMergedTypoScriptObjectTreeIterateOverNodesOnPathAndCollectsTypoScriptConfigurationObjects() {
		$mockFooConfiguration = $this->getMock('F3\TYPO3\Domain\Model\Configuration\TypoScript');
		$mockFooConfiguration->expects($this->once())->method('getSourceCode');
		$mockFooNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$mockFooNode->expects($this->any())->method('getNodeName')->will($this->returnValue('foo'));
		$mockFooNode->expects($this->any())->method('getConfigurations')->will($this->returnValue(array($mockFooConfiguration)));
		$mockBarConfiguration = $this->getMock('F3\TYPO3\Domain\Model\Configuration\TypoScript');
		$mockBarConfiguration->expects($this->once())->method('getSourceCode');
		$mockBarNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$mockBarNode->expects($this->any())->method('getNodeName')->will($this->returnValue('bar'));
		$mockBarNode->expects($this->any())->method('getConfigurations')->will($this->returnValue(array($mockBarConfiguration)));

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site');
		$mockSite->expects($this->any())->method('getSiteResourcesPackageKey')->will($this->returnValue('SitePackage'));
		$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array(), array(), '', FALSE);
		$mockNodeService->expects($this->any())->method('getNodesOnPath')->will($this->returnValue(array($mockFooNode, $mockBarNode)));
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getNodeService')->will($this->returnValue($mockNodeService));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$typoScriptService = $this->getAccessibleMock('F3\TYPO3\Domain\Service\TypoScriptService', array('readExternalTypoScriptFiles'), array($mockContentContext));
		$typoScriptService->_set('typoScriptParser', $this->getMock('F3\TypoScript\Parser', array(), array(), '', FALSE));
		$typoScriptService->getMergedTypoScriptObjectTree('/foo/bar');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getMergedTypoScriptObjectTreeReturnsParsedTypoScriptTree() {
		$mockFooConfiguration = $this->getMock('F3\TYPO3\Domain\Model\Configuration\TypoScript');
		$mockFooConfiguration->expects($this->any())->method('getSourceCode')->will($this->returnValue('FROM CONFIGURATION'));
		$mockFooNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$mockFooNode->expects($this->any())->method('getNodeName')->will($this->returnValue('foo'));
		$mockFooNode->expects($this->any())->method('getConfigurations')->will($this->returnValue(array($mockFooConfiguration)));

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site');
		$mockSite->expects($this->any())->method('getSiteResourcesPackageKey')->will($this->returnValue('SitePackage'));
		$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array(), array(), '', FALSE);
		$mockNodeService->expects($this->any())->method('getNodesOnPath')->will($this->returnValue(array($mockFooNode)));
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getNodeService')->will($this->returnValue($mockNodeService));
		$mockContentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$mockTypoScriptParser = $this->getMock('F3\TypoScript\Parser', array(), array(), '', FALSE);
		$mockTypoScriptParser->expects($this->once())->method('parse')->with('FROM FILE 1' . chr(10) . 'FROM FILE 2' . chr(10) . 'FROM CONFIGURATION' . chr(10))->will($this->returnValue('PARSED TYPOSCRIPT OBJECT TREE'));

		$typoScriptService = $this->getAccessibleMock('F3\TYPO3\Domain\Service\TypoScriptService', array('readExternalTypoScriptFiles'), array($mockContentContext));
		$typoScriptService->_set('typoScriptParser', $mockTypoScriptParser);
		$typoScriptService->expects($this->any())->method('readExternalTypoScriptFiles')->will($this->onConsecutiveCalls('FROM FILE 1', 'FROM FILE 2'));
		$this->assertEquals('PARSED TYPOSCRIPT OBJECT TREE', $typoScriptService->getMergedTypoScriptObjectTree('/foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function readExternalTypoScriptFilesReturnsTypoScriptFilesSortedInNaturalOrder() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('SitePackage'));
		file_put_contents('vfs://SitePackage/Root.ts2', 'ROOT');
		file_put_contents('vfs://SitePackage/4 Four.ts2', 'FOUR');
		file_put_contents('vfs://SitePackage/Default.ts2', 'DEFAULT');
		file_put_contents('vfs://SitePackage/10 Ten.ts2', 'TEN');

		$expectedTypoScript = 'FOUR' . chr(10) . 'TEN' . chr(10) . 'DEFAULT' . chr(10) . 'ROOT' . chr(10);

		$typoScriptService = $this->getAccessibleMock('F3\TYPO3\Domain\Service\TypoScriptService', array('dummy'), array(), '', FALSE);
		$this->assertEquals($expectedTypoScript, $typoScriptService->_call('readExternalTypoScriptFiles', 'vfs://SitePackage/'));
	}

}

?>