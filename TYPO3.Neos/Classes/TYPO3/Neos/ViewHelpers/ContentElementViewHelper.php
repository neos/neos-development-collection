<?php
namespace TYPO3\Neos\ViewHelpers;

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
use TYPO3\Fluid\Core\ViewHelper\TagBuilder;

/**
 * View helper to wrap nodes for editing in the backend
 */
class ContentElementViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * Initialize arguments
	 *
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Wrap the child content in a tag with information about the given node
	 *
	 * Depending on the authentication status additional metadata for editing will be added to the tag.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $tag
	 * @return string The wrapped output
	 */
	public function render(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $tag = 'div') {
		$wrappedTagBuilder = $this->getWrappedTagBuilder($node);

		$wrappedTagBuilder->setTagName($tag);
		$this->applyTagArgumentsFromViewHelper($wrappedTagBuilder);

		return $wrappedTagBuilder->render();
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return \TYPO3\Fluid\Core\ViewHelper\TagBuilder
	 */
	protected function getWrappedTagBuilder(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$fluidTemplateTsObject = $this->templateVariableContainer->get('fluidTemplateTsObject');
		try {
			$content = $this->renderChildren();
		} catch (\Exception $exception) {
			$content = $fluidTemplateTsObject->getTsRuntime()->handleRenderingException($fluidTemplateTsObject->getPath(), $exception);
		}

		return $this->contentElementWrappingService->wrapContentObjectAndReturnTagBuilder($node, $fluidTemplateTsObject->getPath(), $content);
	}

	/**
	 * @param \TYPO3\Fluid\Core\ViewHelper\TagBuilder $tagBuilder The tag builder of the wrapped content
	 * @return void
	 */
	protected function applyTagArgumentsFromViewHelper(TagBuilder $tagBuilder) {
		$tagAttributes = $this->tag->getAttributes();
		foreach ($tagAttributes as $attributeName => $attributeValue) {
			if ($attributeName === 'class') {
				$attributeValue = $tagBuilder->getAttribute('class') . ' ' . $attributeValue;
			}
			$tagBuilder->addAttribute($attributeName, $attributeValue, FALSE);
		}
	}

}
