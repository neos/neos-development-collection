<?php
namespace F3\TYPO3\View\Error;

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
 * A TYPO3 error view with a static template
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class ErrorView extends \F3\FLOW3\MVC\View\NotFoundView {

	/**
	 * Variable names and markers for substitution in static template
	 *
	 * @var array
	 */
	protected $variablesMarker = array(
		'errorTitle' => 'ERROR_TITLE',
		'errorSubtitle' => 'ERROR_SUBTITLE',
		'errorDescription' => 'ERROR_DESCRIPTION'
	);

	/**
	 * Get the template path and filename for the page not found template
	 *
	 * @return string path and filename of the not-found-template
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function getTemplatePathAndFilename() {
		return 'resource://TYPO3/Private/Templates/Frontend/Error/NotFound.html';
	}
}

?>