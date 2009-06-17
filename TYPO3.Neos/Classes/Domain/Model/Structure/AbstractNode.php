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
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 */

/**
 * An Abstract Node
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 */
abstract class AbstractNode implements \F3\TYPO3\Domain\Model\Structure\NodeInterface {

	/**
	 * @var integer
	 */
	protected $childNodesOrder = self::CHILDNODESORDER_UNDEFINED;

	/**
	 * @var array
	 */
	protected $childNodes = array();

	/**
	 * Adds a child node to the list of existing child nodes
	 *
	 * @param \F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode The child node to add
	 * @param \F3\FLOW3\Locale\Locale $locale If specified, the child node is marked with that locale. If not specified, multilingual and international is assumed.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildNode(\F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode, \F3\FLOW3\Locale\Locale $locale = NULL) {
		if ($this->childNodesOrder === self::CHILDNODESORDER_UNDEFINED) {
			$this->childNodesOrder = self::CHILDNODESORDER_ORDERED;
		} elseif ($this->childNodesOrder !== self::CHILDNODESORDER_ORDERED) {
			throw new \F3\TYPO3\Domain\Exception\WrongNodeOrderMethod('This structure node already has child nodes which require a different order method (' . $this->childNodesOrder . ')', 1244641631);
		}
		if ($locale !== NULL) {
			$this->childNodes[$locale->getLanguage()][$locale->getRegion()][] = $childNode;
		} else {
			$this->childNodes['mul']['ZZ'][] = $childNode;
		}
	}

	/**
	 * Sets a child node to which can be refered by the specified name.
	 *
	 * @param string $name The child node's name
	 * @param \F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode The child node
	 * @param \F3\FLOW3\Locale\Locale $locale If specified, the child node is marked with that locale. If not specified, multilingual and international is assumed.
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\WrongNodeOrderMethod if the child node norder is already set and is not "NAMED"
	 * @throws \F3\TYPO3\Domain\Exception\NodeAlreadyExists if a child node with the specified name and locale already exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNamedChildNode($name, \F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode, \F3\FLOW3\Locale\Locale $locale = NULL) {
		if ($this->childNodesOrder === self::CHILDNODESORDER_UNDEFINED) {
			$this->childNodesOrder = self::CHILDNODESORDER_NAMED;
		} elseif ($this->childNodesOrder !== self::CHILDNODESORDER_NAMED) {
			throw new \F3\TYPO3\Domain\Exception\WrongNodeOrderMethod('This structure node already has child nodes which require a different order method (' . $this->childNodesOrder . ')', 1244641632);
		}
		$language = ($locale !== NULL) ? $locale->getLanguage() : 'mul';
		$region = ($locale !== NULL) ? $locale->getRegion() : 'ZZ';

		if (isset($this->childNodes[$language][$region][$name])) {
			throw new \F3\TYPO3\Domain\Exception\NodeAlreadyExists('A child node "' . $name . '" already exists for locale ' . $language . '-' . $region . '. You must remove existing nodes before setting a new one.', 1244807272);
		}
		$this->childNodes[$language][$region][$name] = $childNode;
	}

	/**
	 * Returns the child notes of this structure node.
	 * Note that the child nodes are indexed by language and region!
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale If specified (recommended), only child nodes matching the given locale are returned
	 * @param boolean $useFallBackStrategy If TRUE (default), this function uses a fallback strategy to find alternative nodes if the locale didn't match strictly
	 * @return array Child nodes in the form of array('{language}' => array ('{region}' => {child nodes}))
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodes(\F3\FLOW3\Locale\Locale $locale = NULL, $useFallbackStrategy = TRUE) {
		if ($locale === NULL) {
			return $this->childNodes;
		} else {
			$language = $locale->getLanguage();
			$region = $locale->getRegion();

			if (isset($this->childNodes[$language]) && isset($this->childNodes[$language][$region])) {
				return array($language => array($region => $this->childNodes[$language][$region]));
			}
		}
		return array();
	}

	/**
	 * Tells if this structure node has any child nodes
	 *
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasChildNodes() {
		return $this->childNodes !== array();
	}

	/**
	 * Returns the order of the attached child nodes.
	 *
	 * If no child node has been added yet, the order is undefined. Otherwise the
	 * order is determined by the method how the first child node has been added.
	 *
	 * @return integer One of the CHILDNODEORDER_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodesOrder() {
		return $this->childNodesOrder;
	}
}

?>