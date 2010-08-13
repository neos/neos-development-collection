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
 * An abstract Node
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 * @api
 */
abstract class AbstractNode implements \F3\TYPO3\Domain\Model\Structure\NodeInterface {

	/**
	 * @var string
	 */
	protected $nodeName;

	/**
	 * @var array
	 */
	protected $childNodes = array('default' => array());

	/**
	 * @var \SplObjectStorage<\F3\TYPO3\Domain\Model\Configuration\ConfigurationInterface>
	 */
	protected $configurations;

	/**
	 * Constructs this node
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->configurations = new \SplObjectStorage;
	}

	/**
	 * Sets the name of the node.
	 *
	 * @param string $nodeName The node name
	 * @return void
	 * @api
	 */
	public function setNodeName($nodeName) {
		$this->nodeName = $nodeName;
	}

	/**
	 * Returns the name of the node.
	 * This name is amongst others used for locating the node through a path
	 *
	 * @return string The node name
	 * @api
	 */
	public function getNodeName() {
		return $this->nodeName;
	}

	/**
	 * Adds a child node to the list of existing child nodes
	 *
	 * @param \F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode The node to add
	 * @param \F3\FLOW3\I18n\Locale $locale If specified, the child node is marked with that locale. If not specified, multilingual and international is assumed.
	 * @param string $section Name of the section to which the child node should be added
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function addChildNode(\F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode, \F3\FLOW3\I18n\Locale $locale = NULL, $section = 'default') {
		$language = ($locale !== NULL) ? $locale->getLanguage() : 'mul';
		$region = ($locale !== NULL) ? $locale->getRegion() : 'ZZ';
		$this->childNodes[$section][$language][$region][] = $childNode;
	}

	/**
	 * Returns the child notes of this structure node.
	 * Note that the child nodes are indexed by language and region!
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext The current content context for determining the locale of the nodes to return
	 * @param string $section Name of the section where the child nodes should be located
	 * @return array An array of child nodes. If no context was specified in the form of array('{language}' => array ('{region}' => {child nodes})).
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getChildNodes(\F3\TYPO3\Domain\Service\ContentContext $contentContext = NULL, $section = 'default') {
		if ($contentContext === NULL) {
			return $this->childNodes[$section];
		} else {
			$language = $contentContext->getLocale()->getLanguage();
			$region = $contentContext->getLocale()->getRegion();

			if (isset($this->childNodes[$section][$language]) && isset($this->childNodes[$section][$language][$region])) {
				return $this->childNodes[$section][$language][$region];
			}
		}
		return array();
	}

	/**
	 * Tells if this node has any child nodes
	 *
	 * @param string $sectionName Name of the section to check for child nodes
	 * @return boolean TRUE if the node has child nodes, otherwise FALSE
	 * @api
	 */
	public function hasChildNodes($sectionName = NULL) {
		if ($sectionName === NULL) {
			return $this->childNodes !== array('default' => array());
		}
		if (!isset($this->childNodes[$sectionName])) {
			return FALSE;
		}
		return $this->childNodes[$sectionName] !== array();
	}

	/**
	 * Returns the names of sections for which child nodes have been assigned.
	 * Depending on the node type, further section names might be possible.
	 *
	 * @return array An array of section names which can be used for calling getChildNodes() etc.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getUsedSectionNames() {
		return array_keys($this->childNodes);
	}

	/**
	 * Attaches the given configuration to this node.
	 *
	 * @param \F3\TYPO3\Domain\Model\Configuration\ConfigurationInterface $configuration The configuration to attach
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function addConfiguration(\F3\TYPO3\Domain\Model\Configuration\ConfigurationInterface $configuration) {
		$this->configurations->attach($configuration);
	}

	/**
	 * Returns the configuration objects attached to this node.
	 *
	 * @return \SplObjectStorage The configuration objects
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getConfigurations() {
		return clone $this->configurations;
	}

}

?>