<?php
namespace TYPO3\TYPO3\TypoScript;

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
 * A "Shortcut" TypoScript object
 *
 * @FLOW3\Scope("prototype")
 */
class Shortcut extends \TYPO3\TypoScript\AbstractContentObject {

	/**
	 * Content type of the node this TS Object is based on.
	 *
	 * @var string
	 */
	protected $contentType = 'TYPO3.TYPO3:Shortcut';

	/**
	 * Returns the rendered content of this TypoScript object and sets
	 * a response status of 303 and a location header
	 *
	 * @return string The rendered content as a string
	 */
	public function render() {
		$this->node = $this->renderingContext->getContentContext()->getCurrentNode();

		while ($this->node->getContentType() === 'TYPO3.TYPO3:Shortcut') {
			$childNodes = $this->node->getChildNodes('TYPO3.TYPO3:Page,TYPO3.TYPO3:Shortcut');
			$this->node = current($childNodes);
		}

		$uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();
		$uri = $uriBuilder
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->setFormat($this->renderingContext->getControllerContext()->getRequest()->getFormat())
			->uriFor(NULL, array('node' => $this->node));

		$response = $this->renderingContext->getControllerContext()->getResponse();
		$response->setStatus(303);
		$response->setHeader('Location', $uri);
		return '<html><head><meta http-equiv="refresh" content="0;url=' . htmlentities($uri, ENT_QUOTES, 'utf-8') . '"/></head></html>';
	}
}
?>