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
use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation;

/**
 * Renders a wrapper around the inner contents of the tag to enable frontend editing.
 * The wrapper contains the property name which should be made editable, and is by default
 * a "div" tag. The tag to use can be given as `tag` argument to the ViewHelper.
 */
class EditableViewHelper extends AbstractTagBasedViewHelper {

	/**
	 * @Flow\Inject
	 * @var AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
	}

	/**
	 * In live workspace this just renders a tag with the specified $tag-name containing the value of the given $property.
	 * For logged in users with access to the Backend this also adds required attributes for the RTE to work.
	 *
	 * @param string $property Name of the property to render. Note: If this tag has child nodes, they overrule this argument!
	 * @param string $tag The name of the tag that should be wrapped around the property. By default this is a <div>
	 * @return string The rendered property with a wrapping tag. In the user workspace this adds some required attributes for the RTE to work
	 * @throws ViewHelperException
	 */
	public function render($property, $tag = 'div') {
		$this->tag->setTagName($tag);
		$this->tag->forceClosingTag(TRUE);
		$content = $this->renderChildren();
		if ($content === NULL) {
			if (!$this->templateVariableContainer->exists($property)) {
				throw new ViewHelperException(sprintf('The property "%s" is not accessible', $property), 1384507046);
			}
			$content = $this->templateVariableContainer->get($property);
		}
		$this->tag->setContent($content);

		if (!$this->templateVariableContainer->exists('fluidTemplateTsObject')) {
			throw new ViewHelperException('This ViewHelper can only be used in a TypoScript Content Element', 1385737102);
		}
		/** @var $fluidTemplateTsObject TemplateImplementation */
		$fluidTemplateTsObject = $this->templateVariableContainer->get('fluidTemplateTsObject');
		$currentContext = $fluidTemplateTsObject->getTsRuntime()->getCurrentContext();

		/** @var $contentContext ContentContext */
		$contentContext = $currentContext['node']->getContext();
		if ($contentContext->getWorkspaceName() === 'live' || !$this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess')) {
			return $this->tag->render();
		}

		$this->tag->addAttribute('property', 'typo3:' . $property);
		$this->tag->addAttribute('class', $this->tag->hasAttribute('class') ? 'neos-inline-editable ' . $this->tag->getAttribute('class') : 'neos-inline-editable');
		return $this->tag->render();
	}
}
