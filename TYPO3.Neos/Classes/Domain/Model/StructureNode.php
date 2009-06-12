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
 * A Structure Node
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 */
class StructureNode {

	const CHILDNODESORDER_UNDEFINED = 0;
	const CHILDNODESORDER_ORDERED = 1;
	const CHILDNODESORDER_NAMED = 2;

	/**
	 * @var integer
	 */
	protected $childNodesOrder = self::CHILDNODESORDER_UNDEFINED;

	/**
	 * @var array
	 */
	protected $childNodes = array();

	/**
	 * @var string
	 */
	protected $contentType;

	/**
	 * @var array
	 */
	protected $contents = array();

	/**
	 * Adds a child node to the list of existing child nodes
	 *
	 * @param \F3\TYPO3\Domain\Model\StructureNode $childNode The child node to add
	 * @param \F3\FLOW3\Locale\Locale $locale If specified, the child node is marked with that locale. If not specified, multilingual and international is assumed.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildNode(\F3\TYPO3\Domain\Model\StructureNode $childNode, \F3\FLOW3\Locale\Locale $locale = NULL) {
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
	 * @param \F3\TYPO3\Domain\Model\StructureNode $childNode The child node
	 * @param \F3\FLOW3\Locale\Locale $locale If specified, the child node is marked with that locale. If not specified, multilingual and international is assumed.
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\WrongNodeOrderMethod if the child node norder is already set and is not "NAMED"
	 * @throws \F3\TYPO3\Domain\Exception\NodeAlreadyExists if a child node with the specified name and locale already exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNamedChildNode($name, \F3\TYPO3\Domain\Model\StructureNode $childNode, \F3\FLOW3\Locale\Locale $locale = NULL) {
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

	/**
	 * Attaches the given content object to this structure node.
	 *
	 * A structure node can refer to multiple content objects, but it can only relate to one content object
	 * per locale. The locale information is retrieved directly from the content object on calling this method.
	 * Any existing content with the same locale will be silently overwritten on calling this function.
	 * Note that the criteria for "same locale" is the language and region. Script and variant are not taken
	 * into account.
	 *
	 * Content added to a structure node must always match the type (ie. class name) of previously set content
	 * objects. The first content object added with this method sets the content type for the structure node.
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\ContentInterface $content The content to attach to this structure node
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\InvalidContentType if the content does not matche the type of previously added content.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContent(\F3\TYPO3\Domain\Model\Content\ContentInterface $content) {
		if ($this->contentType !== NULL && get_class($content) !== $this->contentType) {
			throw new \F3\TYPO3\Domain\Exception\InvalidContentType('The given content was of type "' . get_class($content) . '" but the structure node already contains content of type "' . $this->contentType . '". Content types must not be mixed.', 1244713160);
		}
		$locale = $content->getLocale();
		$this->contents[$locale->getLanguage()][$locale->getRegion()] = $content;
		$this->contentType = get_class($content);
	}

	/**
	 * Returns the content specified by $locale.
	 *
	 * This function tries to return a content object which strictly matches the specified locale.
	 * If no such content exists and $useFallBackStrategy is not disabled, it will try to find
	 * a content object which is suggested by a fallback strategy. Such a content object would then
	 * have a locale differing from the specified locale.
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale Locale the content should match
	 * @param boolean $useFallBackStrategy If TRUE (default), this function uses a fallback strategy to find content if the locale didn't match strictly
	 * @return \F3\TYPO3\Domain\Model\Content\ContentInterface The content object or NULL if none matched the given locale
	 * @author Robert Lemke <rober@typo3.org>
	 */
	public function getContent(\F3\FLOW3\Locale\Locale $locale, $useFallbackStrategy = TRUE) {
		$language = $locale->getLanguage();
		$region = $locale->getRegion();

		if (isset($this->contents[$language]) && isset($this->contents[$language][$region])) {
			return $this->contents[$language][$region];
		}
		return NULL;
	}

	/**
	 * @param \F3\TYPO3\Domain\Model\Content\ContentInterface $content The content to attach to this structure node
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\NoSuchContent if the specified content is not currently attached to this structure node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeContent(\F3\TYPO3\Domain\Model\Content\ContentInterface $content) {
		$locale = $content->getLocale();
		$language = $locale->getLanguage();
		$region = $locale->getRegion();

		if (!isset($this->contents[$language]) || !isset($this->contents[$language][$region])) {
			throw new \F3\TYPO3\Domain\Exception\NoSuchContent('The specified content with locale ' . $language . '-' . $region . ' is not attached to this structure node.', 1244802597);
		}
		unset($this->contents[$language][$region]);
		if ($this->contents[$language] === array()) {
			unset($this->contents[$language]);
		}
		if ($this->contents === array()) {
			$this->contentType = NULL;
		}
	}

	/**
	 * Returns the type (class name) of the content attached to this structure node.
	 *
	 * @return string The content type (class name) or NULL if no content is attached to this node yet
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentType() {
		return $this->contentType;
	}
}

?>