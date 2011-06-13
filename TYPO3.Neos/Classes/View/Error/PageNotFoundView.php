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
 * A page not found error view
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageNotFoundView extends \F3\TYPO3\View\Error\ErrorView {

	/**
	 * Pre-filled variables for page not found labels
	 *
	 * @var array
	 */
	protected $variables = array(
		'errorTitle' => 'Ooops, it looks like we\'ve made a mistake, something has gone wrong with this page.',
		'errorSubtitle' => 'Technical reason:<br/>404 - Page not found.',
		'errorDescription' => 'It seems something has gone wrong, the page you where looking for either does not exist or there has been an error in the URL. There is a good chance that this is not something you\'ve done wrong, but an error that we\'re not yet aware of.'
	);

}

?>