<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Domain::Model;

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
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 */

/**
 * Domain model of a site
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 * @entity
 */
class Site {

	/**
	 * The site's unique identifier
	 * @var string
	 * @identifier
	 */
	protected $id;

	/**
	 * Name of the site
	 * @var string
	 */
	protected $name = 'Untitled Site';

	/**
	 * @var F3::TYPO3::Domain::Model::StructureNode
	 */
	protected $rootStructureNode;

	/**
	 * Constructs the new site
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3::FLOW3::Object::FactoryInterface $objectFactory) {
		$this->id = F3::FLOW3::Utility::Algorithms::generateUUID();
		$this->rootStructureNode = $objectFactory->create('F3::TYPO3::Domain::Model::StructureNode');
	}

	/**
	 * Returns the identifier of this site
	 * @return string The site's UUID
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Sets the name for this site
	 *
	 * @param string $name The site name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the name of this site
	 *
	 * @return string The name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the root node of this site's structure tree
	 *
	 * @param F3::TYPO3::Domain::Model::StructureNode
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRootStructureNode(F3::TYPO3::Domain::Model::StructureNode $rootStructureNode) {
		$this->rootStructureNode = $rootStructureNode;
	}

	/**
	 * Returns the root node of this site
	 *
	 * @return F3::TYPO3::Domain::Model::StructureNode
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRootStructureNode() {
		return $this->rootStructureNode;
	}
}

?>