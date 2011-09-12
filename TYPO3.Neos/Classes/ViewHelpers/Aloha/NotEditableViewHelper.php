<?php
namespace TYPO3\TYPO3\ViewHelpers\Aloha;

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
 * Renders a wrapper around the inner contents of the tag to enable frontend editing.
 * The wrapper contains the property name which should be made editable, and is either a "span" or a "div" tag (depending on the context)
 *
 * @scope prototype
 */
class NotEditableViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @var \TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * Injects the access decision manager
	 *
	 * @param \TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface $accessDecisionManager The access decision manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectAccessDecisionManager(\TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface $accessDecisionManager) {
		$this->accessDecisionManager = $accessDecisionManager;
	}

	/**
	 * @param string $property the property to render
	 * @param string $context either "inline" or "block"
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render() {
		if ($this->hasAccessToResource('TYPO3_TYPO3_Backend_BackendController')) {
			$this->tag->setContent($this->renderChildren());
			$this->tag->addAttribute('class', 'typo3-typo3-notEditable');
			return $this->tag->render();
		} else {
			return $this->renderChildren();
		}
	}

	/**
	 * Check if we currently have access to the given resource
	 *
	 * @param string $resource The resource to check
	 * @return boolean TRUE if we currently have access to the given resource
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function hasAccessToResource($resource) {
		try {
			$this->accessDecisionManager->decideOnResource($resource);
		} catch (\TYPO3\FLOW3\Security\Exception\AccessDeniedException $e) {
			return FALSE;
		}

		return TRUE;
	}
}
?>