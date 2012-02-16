<?php
namespace TYPO3\TYPO3\Tests\Functional\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU General Public              *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for two column content element rendering
 */
class TwoColumnTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * @var \TYPO3\TYPO3\Domain\Service\ContentContext
	 */
	protected $siteNode;

	/**
	 * Reset the test dependencies
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->objectManager->forgetInstance('TYPO3\TYPO3CR\Domain\Repository\NodeRepository');
	}

	/**
	 * @test
	 */
	public function renderingTwoColumnNodesWithContent() {
		$this->setUpContextAndSite('user-foo');

		$twoColumnNode = $this->siteNode->createNode('quux', 'TYPO3.TYPO3:TwoColumn');
		$leftSection = $twoColumnNode->createNode('left', 'TYPO3.TYPO3:Section');
		$leftText = $leftSection->createNode('sadasdasd', 'TYPO3.TYPO3:Text');
		$leftText->setProperty('headline', 'My headline left column');
		$leftText->setProperty('text', 'Left col content');

		$rightSection = $twoColumnNode->createNode('right', 'TYPO3.TYPO3:Section');
		$rightText = $rightSection->createNode('sadasdasd', 'TYPO3.TYPO3:Text');
		$rightText->setProperty('headline', 'My headline right column');
		$rightText->setProperty('text', 'Right col content');

		$xml = $this->renderNode($twoColumnNode);

		$this->assertSelectCount('div[data-__nodepath="/sites/testSite/quux@user-foo"] div[data-__nodepath="/sites/testSite/quux/left/sadasdasd@user-foo"]', 1, $xml, 'Content element should render left column');
		$this->assertSelectCount('div[data-__nodepath="/sites/testSite/quux@user-foo"] div[data-__nodepath="/sites/testSite/quux/right/sadasdasd@user-foo"]', 1, $xml, 'Content element should render right column');

		$this->assertSelectRegExp('div[data-__nodepath="/sites/testSite/quux/left/sadasdasd@user-foo"] h1', '/My headline left column/', 1, $xml, 'Content element should render nested content in left column');
		$this->assertSelectRegExp('div[data-__nodepath="/sites/testSite/quux/right/sadasdasd@user-foo"] h1', '/My headline right column/', 1, $xml, 'Content element should render nested content in right column');
	}

	/**
	 * @test
	 */
	public function renderingTwoColumnNodesWithNoContentInRightColumn() {
		$this->setUpContextAndSite('user-foo');

		$twoColumnNode = $this->siteNode->createNode('quux', 'TYPO3.TYPO3:TwoColumn');
		$leftSection = $twoColumnNode->createNode('left', 'TYPO3.TYPO3:Section');
		$leftText = $leftSection->createNode('sadasdasd', 'TYPO3.TYPO3:Text');
		$leftText->setProperty('headline', 'My headline left column');
		$leftText->setProperty('text', 'Left col content');

		$rightSection = $twoColumnNode->createNode('right', 'TYPO3.TYPO3:Section');

		$xml = $this->renderNode($twoColumnNode);

		$this->assertSelectCount('div[data-__nodepath="/sites/testSite/quux@user-foo"] div[data-__nodepath="/sites/testSite/quux/left/sadasdasd@user-foo"]', 1, $xml, 'Content element should render left column');

		$this->assertSelectRegExp('div[data-__nodepath="/sites/testSite/quux/left/sadasdasd@user-foo"] h1', '/My headline left column/', 1, $xml, 'Section should render nested content in left column');
		$this->assertSelectCount('.t3-two-column-right .t3-create-new-content', 1, $xml, 'Section should render button in right column');
	}

	/**
	 * Set up a content context and a site node
	 *
	 * @param string $workspaceName
	 * @return void
	 */
	protected function setUpContextAndSite($workspaceName) {
		$this->contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('user-foo');
		$rootNode = $this->contentContext->getWorkspace()->getRootNode();

		$sitesNode = $rootNode->createNode('sites');
		$this->siteNode = $sitesNode->createNode('testSite');

		$testSite = new \TYPO3\TYPO3\Domain\Model\Site('testSite');
		$this->contentContext->setCurrentSite($testSite);
	}

	/**
	 * Simulate rendering of a node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node
	 * @return string
	 */
	protected function renderNode(\TYPO3\TYPO3CR\Domain\Model\Node $node) {
		$this->contentContext->setCurrentNode($node);

		$renderingContext = new \TYPO3\TypoScript\RenderingContext();
		$renderingContext->setContentContext($this->contentContext);
		$renderingContext->setControllerContext($this->getMock('TYPO3\FLOW3\MVC\Controller\ControllerContext', array(), array(), '', FALSE));

		$firstLevelTypoScriptObject = new \TYPO3\TYPO3\TypoScript\Node();
		$firstLevelTypoScriptObject->setNode($node);
		$firstLevelTypoScriptObject->setRenderingContext($renderingContext);

		return $firstLevelTypoScriptObject->render();
	}

}
?>