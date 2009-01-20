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
 * Contract for a Content object
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
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