<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Domain::Model::Content;

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
 * @version $Id$
 */

/**
 * Domain model of a Text content element
 *
 * @package TYPO3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 * @entity
 */
class Text extends F3::TYPO3::Domain::Model::AbstractContent {

	/**
	 * Headline for this text element
	 * @var string
	 */
 	protected $headline = '';

 	/**
	 * The text of this text element
	 * @var string
	 */
 	protected $text = '';

	/**
	 * Returns a label for this Text element
	 *
	 * @return string The label
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLabel() {
		return $this->headline;
	}

}

?>