<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Controller\Frontend;

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
 * TYPO3's generic error controller
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ErrorController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * Displays a 404 Not Found error screen
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function notFoundAction() {
		$this->view->assign('errorTitle', 'Ooops, it looks like we\'ve made a mistake, something has gone wrong with this page.');
		$this->view->assign('errorSubTitle', 'Technically speaking we have a 404 - Page not found.');
		$this->view->assign('errorDescription', 'It seems something has gone wrong, the page you where looking for either does not exist or there has been an error in the URL. There is a good chance that this is not something you\'ve done wrong, but an error that we\'re not yet aware of.');
	}
}
?>