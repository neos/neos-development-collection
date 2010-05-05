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
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
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
	 * SSets the name of the node.
	 *
	 * @param string $nodeName The node name
	 * @return void
	 */
	public function setNodeName($nodeName) {
		$this->nodeName = $nodeName;
	}

	/**
	 * Returns the name of the node.
	 * This name is amongst others used for locating the node through a path
	 *
	 * @return string The node name
	 */
	public function getNodeName() {
		return $this->nodeName;
	}

	/**
	 * Adds a child node to the list of existing child nodes
	 *
	 * @param \F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode The node to add
	 * @param \F3\FLOW3\Locale\Locale $locale If specified, the child node is marked with that locale. If not specified, multilingual and international is assumed.
	 * @param string $section Name of the section to which the child node should be added
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\InvalidSection
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildNode(\F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode, \F3\FLOW3\Locale\Locale $locale = NULL, $section = 'default') {
		$this->throwExceptionOnInvalidSection($section);
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
	 */
	public function getChildNodes(\F3\TYPO3\Domain\Service\ContentContext $contentContext = NULL, $section = 'default') {
		$this->throwExceptionOnInvalidSection($section);
		$locale = ($contentContext !== NULL) ? $contentContext->getLocale() : NULL;
		if ($locale === NULL) {
			return $this->childNodes[$section];
		} else {
			$language = $locale->getLanguage();
			$region = $locale->getRegion();

			if (isset($this->childNodes[$section][$language]) && isset($this->childNodes[$section][$language][$region])) {
				return $this->childNodes[$section][$language][$region];
			}
		}
		return array();
	}

	/**
	 * Tells if this node has any child nodes
	 *
	 * @param string $section Should not be specified for this kind of node.
	 * @return boolean TRUE if the node has child nodes, otherwise FALSE
	 */
	public function hasChildNodes($section = NULL) {
		if ($section !== NULL) {
			$this->throwExceptionOnInvalidSection($section);
		}
		return $this->childNodes !== array('default' => array());
	}

	/**
	 * Returns the names of sections for which child nodes have been assigned.
	 * Depending on the node type, further section names might be possible.
	 *
	 * @return array An array of section names which can be used for calling getChildNodes() etc.
	 * @author Robert Lemke <robert@typo3.org>
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
	 */
	public function addConfiguration(\F3\TYPO3\Domain\Model\Configuration\ConfigurationInterface $configuration) {
		$this->configurations->attach($configuration);
	}

	/**
	 * Returns the configuration objects attached to this node.
	 *
	 * @return \SplObjectStorage The configuration objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurations() {
		return clone $this->configurations;
	}

	/**
	 * Throws an exception if the given section is not supported by this node.
	 *
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\InvalidSection
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function throwExceptionOnInvalidSection($section) {
		if ($section !== 'default') {
			throw new \F3\TYPO3\Domain\Exception\InvalidSection('Nodes of type "' . get_class($this) . '" only support the "default" section.', 1257780024);
		}
	}
}

?>