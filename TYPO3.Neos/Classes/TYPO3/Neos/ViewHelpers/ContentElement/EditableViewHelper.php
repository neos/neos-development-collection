<?php
namespace TYPO3\Neos\ViewHelpers\ContentElement;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Renders a wrapper around the inner contents of the tag to enable frontend editing.
 * The wrapper contains the property name which should be made editable, and is by default
 * a "div" tag. The tag to use can be given as `tag` argument to the ViewHelper.
 *
 * @Flow\Scope("prototype")
 */
class EditableViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

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

		if ($this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess')) {
			$this->tag->addAttribute('property', 'typo3:' . $property);
			$classAttribute = 'neos-inline-editable';
			if ($this->tag->hasAttribute('class')) {
				$classAttribute .= ' ' . $this->tag->getAttribute('class');
			}
			$this->tag->addAttribute('class', trim($classAttribute));
		} elseif (empty($content)) {
			return '';
		}
		return $this->tag->render();
	}
}
