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
 * @version $Id$
 */

/**
 * Domain model of a generic content element
 *
 * @package TYPO3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 * @entity
 */
abstract class AbstractContent implements F3::TYPO3::Domain::Model::ContentInterface {

	/**
	 * @var F3::FLOW3::Locale::Locale
	 */
	protected $locale;

	/**
	 * Specifies the locale of the content object
	 *
	 * @param F3::FLOW3::Locale::Locale $locale The locale of the content
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setLocale(F3::FLOW3::Locale::Locale $locale) {
		$this->locale = $locale;
	}

	/**
	 * Returns the locale of the content object
	 *
	 * @return F3::FLOW3::Locale::Locale $locale The locale of the content
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLocale() {
		return $this->locale;
	}


	/**
	 * Returns a short string which can be used to label the content object
	 *
	 * @return string A label for the content object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLabel() {
		return '[' . get_class($this) . ']';
	}

}
?>