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
 * Domain model of a site
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 * @api
 */
class Site extends \F3\TYPO3\Domain\Model\Structure\AbstractNode implements \F3\TYPO3\Domain\Model\Structure\IndexNodeAwareInterface {

	/**
	 * Site statusses
	 */
	const STATE_ONLINE = 1;
	const STATE_OFFLINE = 2;

	/**
	 * Name of the site
	 * @var string
	 * @validate Label, StringLength(minimum = 1, maximum = 255)
	 * @api
	 */
	protected $name = 'Untitled Site';

	/**
	 * The site's state
	 * @var integer
	 * @validate NumberRange(minimum = 1, maximum = 2)
	 */
	protected $state = self::STATE_ONLINE;

	/**
	 * @var string
	 */
	protected $siteResourcesPackageKey;

	/**
	 * Sets the name for this site
	 *
	 * @param string $name The site name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the name of this site
	 *
	 * @return string The name
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the state for this site
	 *
	 * @param integer $state The site's state, must be one of the STATUS_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setState($state) {
		$this->state = $state;
	}

	/**
	 * Returns the state of this site
	 *
	 * @return integer The state - one of the STATUS_* constant's values
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * Sets the key of a package containing the static resources for this site.
	 *
	 * @param string $packageKey The package key
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setSiteResourcesPackageKey($packageKey) {
		$this->siteResourcesPackageKey = $packageKey;
	}

	/**
	 * Returns the key of a package containing the static resources for this site.
	 *
	 * @return string The package key
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getSiteResourcesPackageKey() {
		return $this->siteResourcesPackageKey;
	}

	/**
	 * Adds a child node to the list of existing child nodes
	 *
	 * @param \F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode The node to add
	 * @param \F3\FLOW3\Locale\Locale $locale If specified, the child node is marked with that locale. If not specified, multilingual and international is assumed.
	 * @param string $section Must be "default"!
	 * @return void
	 * @throws \InvalidArgumentException if $section is not "default"
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function addChildNode(\F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode, \F3\FLOW3\Locale\Locale $locale = NULL, $section = 'default') {
		if ($section !== 'default') {
			throw new \InvalidArgumentException('Site structure nodes can only have children added to the "default" section.', 1276616370);
		}
		parent::addChildNode($childNode, $locale, 'default');
	}

	/**
	 * Returns the child notes of this structure node.
	 * Note that the child nodes are indexed by language and region!
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext The current content context for determining the locale of the nodes to return
	 * @param string $section Always "default", will be ignored if given
	 * @return array An array of child nodes. If no context was specified in the form of array('{language}' => array ('{region}' => {child nodes})).
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getChildNodes(\F3\TYPO3\Domain\Service\ContentContext $contentContext = NULL, $section = 'default') {
		return parent::getChildNodes($contentContext, 'default');
	}

	/**
	 * Returns the index node of this site
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext The current content context
	 * @return \F3\TYPO3\Domain\Model\Structure\ContentNode The index node or NULL if no index node exists.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getIndexNode(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$childNodesMatchingContext = $this->getChildNodes($contentContext, 'default');
		return reset($childNodesMatchingContext) ?: NULL;
	}

}

?>