<?php
namespace TYPO3\Neos\ViewHelpers\Link;

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
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * A view helper for creating links with URIs pointing to nodes.
 *
 * The target node can be provided as string or as a Node object; if not specified
 * at all, the generated URI will refer to the current node.
 *
 * When specifying the ``node`` argument as string, the following conventions apply:
 *
 * *``node`` starts with ``/``:*
 * The given path is an absolute node path and is treated as such.
 * Example: ``/sites/acmecom/home/about/us``
 *
 * *``node`` does not start with ``/``:*
 * The given path is treated as a path relative to the current node.
 * Examples: given that the current node is ``/sites/acmecom/products/``,
 * ``stapler`` results in ``/sites/acmecom/products/stapler``,
 * ``../about`` results in ``/sites/acmecom/about/``
 * ``./neos/info`` results in ``/sites/acmecom/products/neos/info``
 *
 * *``node`` starts with a tilde character (``~``):*
 * The given path is treated as a path relative to the current site node.
 * Example: given that the current node is ``/sites/acmecom/products/``,
 * ``~/about/us`` results in ``/sites/acmecom/about/us``.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <neos:link.node>some link</neos:link.node>
 * </code>
 * <output>
 * <a href="sites/mysite.com/homepage/about.html">some link</a>
 * (depending on current node, format etc.)
 * </output>
 *
 * <code title="Generating a link with an absolute URI">
 * <neos:link.node absolute="{true"}>bookmark this page</neos:link.node>
 * </code>
 * <output>
 * <a href="http://www.example.org/homepage/about.html">bookmark this page</a>
 * (depending on current workspace, current node, format, host etc.)
 * </output>
 *
 * <code title="Target node given as absolute node path">
 * <neos:link.node node="/sites/exampleorg/contact/imprint">Corporate imprint</neos:link.node>
 * </code>
 * <output>
 * <a href="contact/imprint.html">Corporate imprint</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * @api
 */
class NodeViewHelper extends AbstractTagBasedViewHelper {

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
	 * @param mixed $node A TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface object or a string node path
	 * @param string $format Format to use for the URL, for example "html" or "json"
	 * @param boolean $absolute If set, an absolute URI is rendered
	 * @return string The rendered link
	 */
	public function render($node = NULL, $format = NULL, $absolute = FALSE) {
		$currentContext = $this->nodeRepository->getContext();
		if ($currentContext === NULL) {
			$currentContext = new ContentContext('live');
			$this->nodeRepository->setContext($currentContext);
		}
		if ($node === NULL) {
			$node = $currentContext->getCurrentNode();
		} elseif (is_string($node)) {
			if (substr($node, 0, 2) === '~/') {
				$node = $currentContext->getCurrentSiteNode()->getNode(substr($node, 2));
			} else {
				if (substr($node, 0, 1) === '/') {
					$node = $currentContext->getNode($node);
				} else {
					$node = $currentContext->getCurrentNode()->getNode($node);
				}
			}
		}

		if ($node instanceof NodeInterface) {
			$request = $this->controllerContext->getRequest()->getMainRequest();

			if ($format === NULL) {
				$format = $request->getFormat();
			}

			$uriBuilder = clone $this->controllerContext->getUriBuilder();
			$uriBuilder->setRequest($request);
			$uri = $uriBuilder
				->reset()
				->setCreateAbsoluteUri($absolute)
				->setFormat($format)
				->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos');
			$this->tag->addAttribute('href', $uri);
		}

		$this->tag->setContent($this->renderChildren());
		return $this->tag->render();
	}

}
?>