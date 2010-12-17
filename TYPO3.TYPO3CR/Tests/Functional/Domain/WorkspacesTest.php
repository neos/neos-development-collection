<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Tests\Functional\Domain;

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
class WorkspacesTest extends \F3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testablePersistenceEnabled = TRUE;

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function nodesCreatedInAPersonalWorkspacesCanBeRetrievedAgainInThePersonalContext() {
		$context = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', 'user-robert');
		$rootNode = $context->getWorkspace()->getRootNode();
		$fooNode = $rootNode->createNode('foo');
		$this->assertSame($fooNode, $rootNode->getNode('foo'));

		$this->persistenceManager->persistAll();

		$this->assertSame($fooNode, $rootNode->getNode('foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function nodesCreatedInAPersonalWorkspacesAreNotVisibleInTheLiveWorkspace() {
		$personalContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', 'user-robert');
		$personalRootNode = $personalContext->getWorkspace()->getRootNode();
		$personalRootNode->createNode('homepage')->createNode('about');

		$this->persistenceManager->persistAll();

		$liveContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', 'live');
		$liveRootNode = $liveContext->getWorkspace()->getRootNode();

		$this->assertNull($liveRootNode->getNode('/homepage/about'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function nodesCreatedInAPersonalWorkspacesAreNotVisibleInTheLiveWorkspaceEvenWithoutPersistAll() {
		$personalContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', 'user-robert');
		$personalRootNode = $personalContext->getWorkspace()->getRootNode();
		$personalRootNode->createNode('homepage')->createNode('about');

		$liveContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', 'live');
		$liveRootNode = $liveContext->getWorkspace()->getRootNode();

		$this->assertNull($liveRootNode->getNode('/homepage/about'));
	}
}