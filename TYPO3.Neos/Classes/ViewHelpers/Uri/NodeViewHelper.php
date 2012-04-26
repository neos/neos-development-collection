<?php
namespace TYPO3\TYPO3\ViewHelpers\Uri;

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
 * A view helper for creating URIs to nodes.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <typo3:uri.node />
 * </code>
 *
 * Output:
 * homepage/about.html
 * (depending on current workspace, current node, format etc.)
 *
 * @FLOW3\Scope("prototype")
 */
class NodeViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Render the Uri.
	 *
	 * @param mixed $node A node object or a node path
	 * @param string $format Format to use for the URL, for example "html" or "json"
	 * @param boolean $absolute If set, an absolute URI is rendered
	 * @return string The rendered URI
	 */
	public function render($node = NULL, $format = NULL, $absolute = FALSE) {
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$request = $this->controllerContext->getRequest();

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

		return $uri;
	}
}
?>