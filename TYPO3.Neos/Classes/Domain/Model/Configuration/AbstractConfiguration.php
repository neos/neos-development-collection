<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Configuration;

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
 * An abstract domain model of configuration which can be attached to a structure node.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 * @api
 */
abstract class AbstractConfiguration implements \F3\TYPO3\Domain\Model\Configuration\ConfigurationInterface {

	/**
	 * Returns a short string which can be used to label the configuration object
	 *
	 * @return string A label for the configuration object
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getLabel() {
		return '[' . get_class($this) . ']';
	}

}
?>