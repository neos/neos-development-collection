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
 * Domain model of a site
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 */
class Site extends \F3\TYPO3\Domain\Model\Structure\AbstractNode {

	/**
	 * Site statusses
	 */
	const STATE_ONLINE = 1;
	const STATE_OFFLINE = 2;

	/**
	 * Name of the site
	 * @var string
	 * @validate AlphaNumeric, StringLength(minimum = 1, maximum = 255)
	 */
	protected $name = 'Untitled Site';

	/**
	 * The site's state
	 * @var integer
	 * @validate NumberRange(minimum = 1, maximum = 2)
	 */
	protected $state = self::STATE_ONLINE;

	/**
	 * Constructs this site model
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->domains = new \SplObjectStorage;
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
	 * Sets the state for this site
	 *
	 * @param integer $state The site's state, must be one of the STATUS_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setState($state) {
		$this->state = $state;
	}

	/**
	 * Returns the state of this site
	 *
	 * @return integer The state - one of the STATUS_* constant's values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * Returns the root node of this site
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext The current content context
	 * @return \F3\TYPO3\Domain\Model\Structure\ContentNode
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRootNode(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$childNodesMatchingContext = $this->getChildNodes($contentContext);
		return current($childNodesMatchingContext);
	}

}

?>