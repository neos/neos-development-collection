<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
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
 * Functional test case which covers all workspace-related behavior of the
 * content repository.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class WorkspacesTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3\Domain\Service\ContentContext
	 */
	protected $personalContext;

	/**
	 * @var \TYPO3\TYPO3\Domain\Model\Node
	 */
	protected $rootNode;

	/**
	 * @return void
	 */
	public function setup() {
		parent::setup();
		$this->personalContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('user-robert');
		$this->rootNode = $this->personalContext->getWorkspace()->getRootNode();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function nodesCreatedInAPersonalWorkspacesCanBeRetrievedAgainInThePersonalContext() {
		$fooNode = $this->rootNode->createNode('foo');
		$this->assertSame($fooNode, $this->rootNode->getNode('foo'));

		$this->persistenceManager->persistAll();

		$this->assertSame($fooNode, $this->rootNode->getNode('foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function nodesCreatedInAPersonalWorkspacesAreNotVisibleInTheLiveWorkspace() {
		$this->rootNode->createNode('homepage')->createNode('about');

		$this->persistenceManager->persistAll();

		$liveContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('live');
		$liveRootNode = $liveContext->getWorkspace()->getRootNode();

		$this->assertNull($liveRootNode->getNode('/homepage/about'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function nodesCreatedInAPersonalWorkspacesAreNotVisibleInTheLiveWorkspaceEvenWithoutPersistAll() {
		$this->rootNode->getNode('homepage')->createNode('imprint');

		$liveContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('live');
		$liveRootNode = $liveContext->getWorkspace()->getRootNode();

		$this->assertNull($liveRootNode->getNode('/homepage/imprint'));
	}
}