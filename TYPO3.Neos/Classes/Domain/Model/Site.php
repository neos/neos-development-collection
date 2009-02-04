<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model;

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
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 */
class Site {

	/**
	 * The site's unique identifier
	 * @var string
	 * @uuid
	 */
	protected $id;

	/**
	 * Name of the site
	 * @var string
	 */
	protected $name = 'Untitled Site';

	/**
	 * @var \F3\TYPO3\Domain\Model\StructureNode
	 */
	protected $rootStructureNode;

	/**
	 * Constructs the new site
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->id = \F3\FLOW3\Utility\Algorithms::generateUUID();
		$this->rootStructureNode = $objectFactory->create('F3\TYPO3\Domain\Model\StructureNode');
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
	 * @param \F3\TYPO3\Domain\Model\StructureNode
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRootStructureNode(\F3\TYPO3\Domain\Model\StructureNode $rootStructureNode) {
		$this->rootStructureNode = $rootStructureNode;
	}

	/**
	 * Returns the root node of this site
	 *
	 * @return \F3\TYPO3\Domain\Model\StructureNode
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRootStructureNode() {
		return $this->rootStructureNode;
	}
}

?>