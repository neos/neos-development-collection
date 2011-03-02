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
 * Domain model of a site
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 * @api
 */
class Site {

	/**
	 * This ID is only for the ORM.
	 *
	 * @var integer
	 * @Id
	 * @GeneratedValue
	*/
	protected $id;

	/**
	 * Site statusses
	 */
	const STATE_ONLINE = 1;
	const STATE_OFFLINE = 2;

	/**
	 * Name of the site
	 *
	 * @var string
	 * @validate Label, StringLength(minimum = 1, maximum = 250)
	 */
	protected $name = 'Untitled Site';

	/**
	 * Node name of this site in the content repository.
	 *
	 * The first level of nodes of a site can be reached via a path like
	 * "/Sites/MySite/" where "MySite" is the nodeName.
	 *
	 * @var string
	 * @identity
	 */
	protected $nodeName;

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
	 * Constructs this Site object
	 *
	 * @param string $nodeName Node name of this site in the content repository
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($nodeName) {
		$this->nodeName = $nodeName;
	}

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
	 * Returns the node name of this site
	 *
	 * @return string The node name
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getNodeName() {
		return $this->nodeName;
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

}
?>