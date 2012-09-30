<?php
namespace TYPO3\TYPO3\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A view helper for creating links to nodes.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <typo3:link.node>some link</typo3:link.node>
 * </code>
 *
 * Output:
 * <a href="sites/mysite.com/homepage/about.html">some link</a>
 * (depending on current node, format etc.)
 *
 * @Flow\Scope("prototype")
 */
class NodeViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Initialize arguments
	 *
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
		$this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
		$this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
		$this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
	}

	/**
	 * Render the link.
	 *
	 * @param mixed $node A node object or a node path
	 * @param string $format Format to use for the URL, for example "html" or "json"
	 * @param boolean $absolute If set, an absolute URI is rendered
	 * @return string The rendered link
	 */
	public function render($node = NULL, $format = NULL, $absolute = FALSE) {
		$request = $this->controllerContext->getRequest()->getMainRequest();

		$uriBuilder = new \TYPO3\Flow\Mvc\Routing\UriBuilder();
		$uriBuilder->setRequest($request);

		if ($node === NULL) {
			$node = $this->nodeRepository->getContext()->getCurrentNode();
		}

		if ($format === NULL) {
			$format = $request->getFormat();
		}

		$uri = $uriBuilder
			->reset()
			->setCreateAbsoluteUri($absolute)
			->setFormat($format)
			->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.TYPO3');

		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren());

		return $this->tag->render();
	}

}
?>