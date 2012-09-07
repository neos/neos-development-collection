<?php
namespace TYPO3\TYPO3\ViewHelpers\ContentElement;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Renders a wrapper around the inner contents of the tag to enable frontend editing.
 * The wrapper contains the property name which should be made editable, and is either a "span" or a "div" tag (depending on the context)
 *
 * @FLOW3\Scope("prototype")
 */
class EditableViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @var \TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * Injects the access decision manager
	 *
	 * @param \TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface $accessDecisionManager The access decision manager
	 * @return void
	 */
	public function injectAccessDecisionManager(\TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface $accessDecisionManager) {
		$this->accessDecisionManager = $accessDecisionManager;
	}

	/**
	 * @param string $property the property to render
	 * @param string $tag
	 * @return string
	 */
	public function render($property, $tag = 'div') {
		$this->tag->setTagName($tag);
		$this->tag->forceClosingTag(TRUE);

		$content = $this->renderChildren();
		$this->tag->setContent($content);

		if ($this->hasAccessToResource('TYPO3_TYPO3_Backend_BackendController')) {
			$this->tag->addAttribute('property', 'typo3:' . $property);
		} elseif (empty($content)) {
			return '';
		}
		return $this->tag->render();
	}

	/**
	 * Check if we currently have access to the given resource
	 *
	 * @param string $resource The resource to check
	 * @return boolean TRUE if we currently have access to the given resource
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