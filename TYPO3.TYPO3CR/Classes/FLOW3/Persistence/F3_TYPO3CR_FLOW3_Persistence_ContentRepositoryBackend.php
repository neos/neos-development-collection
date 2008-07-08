<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 */

/**
 * A persistence backend for FLOW3 using the Content Repository
 *
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_FLOW3_Persistence_ContentRepositoryBackend implements F3_FLOW3_Persistence_BackendInterface {

	/**
	 * @var F3_PHPCR_SessionInterface
	 */
	protected $session;

	/**
	 * Injects the Content Repository used to persist data
	 *
	 * @param F3_PHPCR_RepositoryInterface $repository
	 * @return void
	 */
#	public function injectContentRepository(F3_PHPCR_RepositoryInterface $repository) {
#		$this->session = $repository->login();
#	}

	/**
	 * Initializes the backend
	 *
	 * @param array $classSchemata the class schemata the backend will be handling
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize(array $classSchemata) {
		// DISABLED FOR NOW
		return;

		$nodeTypeManager = $this->session->getWorkspace()->getNodeTypeManager();

		foreach($classSchemata as $schema) {
			if ($nodeTypeManager->hasNodeType($schema->getClassName())) {
				$nodeTypeManager->unregisterNodeType($schema->getClassName());
			}
			$nodeTypeTemplate = $nodeTypeManager->createNodeTypeTemplate();
			$nodeTypeTemplate->setName($schema->getClassName());
			$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		}
	}

	/**
	 * Sets the new objects
	 *
	 * @param array $objects
	 * @return void
	 */
	public function setNewObjects(array $objects) {

	}


	/**
	 * Sets the updated objects
	 *
	 * @param array $objects
	 * @return void
	 */
	public function setUpdatedObjects(array $objects) {

	}


	/**
	 * Sets the deleted objects
	 *
	 * @param array $objects
	 * @return void
	 */
	public function setDeletedObjects(array $objects) {

	}

	/**
	 * Commits the current persistence session
	 *
	 * @return void
	 */
	public function commit() {

	}
}

?>