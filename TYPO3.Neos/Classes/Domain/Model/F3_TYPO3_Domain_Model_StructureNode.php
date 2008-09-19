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
 * A Structure Node
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 * @entity
 */
class StructureNode {

	/**
	 * The content objects which are assigned to this structure node
	 *
	 * @var array
	 * @reference
	 */
	protected $contents = array();

	/**
	 * Returns the content which is assigned to this structure node.
	 *
	 * If a locale is specified, this node will try to return a matching content object based on the
	 * globally defined fallback rules.
	 *
	 * @param string $languageLocale The language locale of the desired content. Use "und" for undefined language
	 * @param string $countryLocale The country locale of the desired content. Use "ZZ" for any country
	 * @return F3::TYPO3::Domain::Model::Content The content object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContent($languageLocale = 'und', $countryLocale = 'ZZ') {
		foreach ($this->contents as $content) {

		}
	}
}

?>