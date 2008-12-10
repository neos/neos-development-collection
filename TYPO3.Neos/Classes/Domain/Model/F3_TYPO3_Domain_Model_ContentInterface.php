<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model;

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
 * Contract for a Content object
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @author Robert Lemke <robert@typo3.org>
 */
interface ContentInterface {

	/**
	 * Specifies the locale of the content object
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale The locale of the content
	 * @return void
	 */
	public function setLocale(\F3\FLOW3\Locale\Locale $locale);

	/**
	 * Returns the locale of the content object
	 *
	 * @return \F3\FLOW3\Locale\Locale $locale The locale of the content
	 */
	public function getLocale();

	/**
	 * Returns a short string which can be used to label the content object
	 *
	 * @return string A label for the content object
	 */
	public function getLabel();

}
?>