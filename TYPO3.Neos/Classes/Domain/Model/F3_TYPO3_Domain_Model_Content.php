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
class Content {

	/**
	 * @var unknown_type
	 * @transient
	 */
	protected $languageLocale = 'und';

	/**
	 * @var unknown_type
	 * @transient
	 */
	protected $countryLocale = 'ZZ';

	public function getLanguageLocale() {
		return $this->languageLocale;
	}

	public function setLanguageLocal($languageLocale) {
		$this->languageLocale = $languageLocale;
	}

	public function getCountryLocale() {
		return $this->countryLocale;
	}

	public function setCountryLocale($countryLocale) {
		$this->countryLocale = $countryLocale;
	}
}
?>