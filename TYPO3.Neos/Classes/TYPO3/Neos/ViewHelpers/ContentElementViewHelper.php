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

/**
 * ViewHelper which wraps all content elements, and adds an additional div wrapper
 * if we are in backend mode.
 */
class ContentElementViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * Include all JavaScript files matching the include regular expression
	 * and not matching the exclude regular expression.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $class CSS class to apply to, if any
	 * @param boolean $page
	 * @return string
	 */
	public function render(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $class = '', $page = FALSE) {
		$fluidTemplateTsObject = $this->templateVariableContainer->get('fluidTemplateTsObject');
		try {
			$content = $this->renderChildren();
		} catch (\Exception $exception) {
			$content = $fluidTemplateTsObject->getTsRuntime()->handleRenderingException($fluidTemplateTsObject->getPath(), $exception);
		}

		$tagBuilder = $this->contentElementWrappingService->wrapContentObjectAndReturnTagBuilder($node, $fluidTemplateTsObject->getPath(), $content, $page);

		if ($class !== '') {
			$class = ' ' . $class;
		}
		$tagBuilder->addAttribute('class', $tagBuilder->getAttribute('class') . $class);

		return $tagBuilder->render();
	}
}
?>