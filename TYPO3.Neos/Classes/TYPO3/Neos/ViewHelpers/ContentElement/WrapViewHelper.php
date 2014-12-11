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
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;
use TYPO3\Neos\Service\ContentElementWrappingService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptAwareViewInterface;

/**
 * A view helper for manually wrapping content editables.
 *
 * Note that using this view helper is usually not necessary as Neos will automatically wrap editables of content
 * elements.
 *
 * By explicitly wrapping template parts with node meta data that is required for the backend to show properties in the
 * inspector, this ViewHelper enables usage of the ``contentElement.editable`` ViewHelper outside of content element
 * templates. This is useful if you want to make properties of a custom document node inline-editable.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <neos:contentElement.wrap>
 *  <div>{neos:contentElement.editable(property: 'someProperty')}</div>
 * </neos:contentElement.wrap>
 * </code>
 */
class WrapViewHelper extends AbstractViewHelper {

	/**
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;

	/**
	 * @Flow\Inject
	 * @var ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * In live workspace this just renders a the content.
	 * For logged in users with access to the Backend this also adds the attributes for the RTE to work.
	 *
	 * @param NodeInterface $node The node of the content element. Optional, will be resolved from the TypoScript context by default.
	 * @return string The rendered property with a wrapping tag. In the user workspace this adds some required attributes for the RTE to work
	 * @throws ViewHelperException
	 */
	public function render(NodeInterface $node = NULL) {
		$view = $this->viewHelperVariableContainer->getView();
		if (!$view instanceof TypoScriptAwareViewInterface) {
			throw new ViewHelperException('This ViewHelper can only be used in a TypoScript content element. You have to specify the "node" argument if it cannot be resolved from the TypoScript context.', 1385737102);
		}
		$typoScriptObject = $view->getTypoScriptObject();
		$currentContext = $typoScriptObject->getTsRuntime()->getCurrentContext();

		if ($node === NULL) {
			$node = $currentContext['node'];
		}
		return $this->contentElementWrappingService->wrapContentObject($node, $typoScriptObject->getPath(), $this->renderChildren());
	}
}
