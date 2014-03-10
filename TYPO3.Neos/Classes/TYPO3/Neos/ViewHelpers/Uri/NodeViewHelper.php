<?php
namespace TYPO3\Neos\ViewHelpers\Uri;

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
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Neos\Exception as NeosException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptAwareViewInterface;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;

/**
 * A view helper for creating URIs pointing to nodes.
 *
 * The target node can be provided as string or as a Node object; if not specified
 * at all, the generated URI will refer to the current document node inside the TypoScript context.
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
 * <code title="Default">
 * <neos:uri.node />
 * </code>
 * <output>
 * homepage/about.html
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * <code title="Generating an absolute URI">
 * <neos:uri.node absolute="{true"} />
 * </code>
 * <output>
 * http://www.example.org/homepage/about.html
 * (depending on current workspace, current node, format, host etc.)
 * </output>
 *
 * <code title="Target node given as absolute node path">
 * <neos:uri.node node="/sites/acmecom/about/us" />
 * </code>
 * <output>
 * about/us.html
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * @api
 */
class NodeViewHelper extends AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * Render the Uri.
	 *
	 * @param mixed $node A TYPO3\TYPO3CR\Domain\Model\NodeInterface object or a string node path or NULL to resolve the current document node
	 * @param string $format Format to use for the URL, for example "html" or "json"
	 * @param boolean $absolute If set, an absolute URI is rendered
	 * @param string $baseNodeName The name of the base node inside the TypoScript context to use for the ContentContext or resolving relative paths
	 * @param array $arguments Additional arguments to be passed to the UriBuilder (for example pagination parameters)
	 * @return string The rendered URI or NULL if no URI could be resolved for the given node
	 * @throws NeosException
	 * @throws \InvalidArgumentException
	 * @throws ViewHelperException
	 */
	public function render($node = NULL, $format = NULL, $absolute = FALSE, $baseNodeName = 'documentNode', array $arguments = array()) {
		if (!($node === NULL || $node instanceof NodeInterface || is_string($node))) {
			throw new \InvalidArgumentException('Expected NodeInterface, string or NULL for the node argument', 1373101025);
		}

		if (is_string($node)) {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $node, $matches);
			if (isset($matches['WorkspaceName'])) {
				$node = $this->propertyMapper->convert($node, 'TYPO3\TYPO3CR\Domain\Model\NodeInterface');
			}
		}

		if ($node === NULL || is_string($node)) {
			$view = $this->viewHelperVariableContainer->getView();
			if (!$view instanceof TypoScriptAwareViewInterface) {
				throw new ViewHelperException('This ViewHelper can only be used in a TypoScript content element. You have to specify the "node" argument if it cannot be resolved from the TypoScript context.', 1385737102);
			}
			$typoScriptObject = $view->getTypoScriptObject();
			$currentContext = $typoScriptObject->getTsRuntime()->getCurrentContext();
			if (isset($currentContext[$baseNodeName])) {
				$baseNode = $currentContext[$baseNodeName];
			} else {
				throw new NeosException(sprintf('Could not find a node instance in TypoScript context with name "%s" and no node instance was given to the node argument. Set a node instance in the TypoScript context or pass a node object to resolve the URI.', $baseNodeName), 1373100400);
			}

			if (is_string($node)) {
				$contentContext = $baseNode->getContext();
				if (substr($node, 0, 2) === '~/') {
					$node = $contentContext->getCurrentSiteNode()->getNode(substr($node, 2));
				} else {
					if (substr($node, 0, 1) === '/') {
						$node = $contentContext->getNode($node);
					} else {
						$node = $baseNode->getNode($node);
					}
				}
			} else {
				$node = $baseNode;
			}
		}

		if (!$node instanceof NodeInterface) {
			return NULL;
		}
		$request = $this->controllerContext->getRequest()->getMainRequest();

		if ($format === NULL) {
			$format = $request->getFormat();
		}

		$uriBuilder = clone $this->controllerContext->getUriBuilder();
		$uriBuilder->setRequest($request);
		return $uriBuilder
			->reset()
			->setCreateAbsoluteUri($absolute)
			->setArguments($arguments)
			->setFormat($format)
			->uriFor('show', array('node' => $this->convertNode($node)), 'Frontend\Node', 'TYPO3.Neos');
	}

	/**
	 * Converts the given $node to a string being:
	 * - in "live" workspace: The node identifier (not the technical identifier)
	 * - otherwise: The node context path ("some/path@workspace-name")
	 *
	 * @param NodeInterface $node
	 * @return string
	 */
	protected function convertNode(NodeInterface $node) {
		if ($node->getContext()->getWorkspace(FALSE)->getName() === 'live') {
			return $node->getIdentifier();
		}
		return $node->getContextPath();
	}
}
