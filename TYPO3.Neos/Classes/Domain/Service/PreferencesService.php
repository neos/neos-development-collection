<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Service;

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
 * User Preferences, should be lateron stored in the database; but stored in
 * the session right now.
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope session
 */
class PreferencesService {

	/**
	 * Name of the current workspace.
	 * @var string
	 */
	protected $currentWorkspaceName;

	/**
	 * @return string the current workspace
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getCurrentWorkspaceName() {
		return $this->currentWorkspaceName;
	}

	/**
	 * Set the workspace which should be used.
	 * @param string $currentWorkspaceName
	 */
	public function setCurrentWorkspaceName($currentWorkspaceName) {
		$this->currentWorkspaceName = $currentWorkspaceName;
	}
}
?>