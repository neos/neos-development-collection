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
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * View helper to wrap nodes for editing in the backend
 *
 * **Deprecated!** This ViewHelper is no longer needed as wrapping is now done with a TypoScript processor.
 *
 * @deprecated since 1.0
 */
class ContentElementViewHelper extends AbstractTagBasedViewHelper {

	/**
	 * Initialize arguments
	 *
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
	}

	/**
	 * This ViewHelper is no longer used
	 *
	 * @param NodeInterface $node
	 * @param boolean $page
	 * @param string $tag
	 * @return string The wrapped output
	 * @deprecated
	 */
	public function render(NodeInterface $node, $page = FALSE, $tag = 'div') {
		$this->tag->setTagName($tag);
		$this->tag->setContent($this->renderChildren());
		return $this->tag->render();
	}

}
