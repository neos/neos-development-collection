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
use TYPO3\Neos\Exception as NeosException;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Neos\Service\LinkingService;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptAwareViewInterface;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

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
 * ``../about`` results in ``/sites/acmecom/about/``,
 * ``./neos/info`` results in ``/sites/acmecom/products/neos/info``.
 *
 * *``node`` starts with a tilde character (``~``):*
 * The given path is treated as a path relative to the current site node.
 * Example: given that the current node is ``/sites/acmecom/products/``,
 * ``~/about/us`` results in ``/sites/acmecom/about/us``,
 * ``~`` results in ``/sites/acmecom``.
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
 * <code title="Target node given as relative node path">
 * <neos:uri.node node="~/about/us" />
 * </code>
 * <output>
 * about/us.html
 * (depending on current workspace, current node, format etc.)
 * </output>
 * @api
 */
class NodeViewHelper extends AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var LinkingService
	 */
	protected $linkingService;

	/**
	 * Renders the URI.
	 *
	 * @param mixed $node A node object or a string node path (absolute or relative) or NULL to resolve the current document node
	 * @param string $format Format to use for the URL, for example "html" or "json"
	 * @param boolean $absolute If set, an absolute URI is rendered
	 * @param array $arguments Additional arguments to be passed to the UriBuilder (for example pagination parameters)
	 * @param string $section
	 * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
	 * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
	 * @param string $baseNodeName The name of the base node inside the TypoScript context to use for the ContentContext or resolving relative paths
	 * @return string The rendered URI or NULL if no URI could be resolved for the given node
	 * @throws ViewHelperException
	 */
	public function render($node = NULL, $format = NULL, $absolute = FALSE, array $arguments = array(), $section = '', $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array(), $baseNodeName = 'documentNode') {
		$baseNode = NULL;
		if (!$node instanceof NodeInterface) {
			$view = $this->viewHelperVariableContainer->getView();
			if (!$view instanceof TypoScriptAwareViewInterface) {
				throw new ViewHelperException('This ViewHelper can only be used in a TypoScript content element, if you don\'t specify the "node" argument if it cannot be resolved from the TypoScript context.', 1385737102);
			}
			$typoScriptObject = $view->getTypoScriptObject();
			$currentContext = $typoScriptObject->getTsRuntime()->getCurrentContext();
			if (isset($currentContext[$baseNodeName])) {
				$baseNode = $currentContext[$baseNodeName];
			} else {
				throw new ViewHelperException(sprintf('Could not find a node instance in TypoScript context with name "%s" and no node instance was given to the node argument. Set a node instance in the TypoScript context or pass a node object to resolve the URI.', $baseNodeName), 1373100400);
			}
		}
		$controllerContext = $this->controllerContext;

		try {
			return $this->linkingService->createNodeUri(
				$controllerContext,
				$node,
				$baseNode,
				$format,
				$absolute,
				$arguments,
				$section,
				$addQueryString,
				$argumentsToBeExcludedFromQueryString
			);
		} catch (NeosException $exception) {
			$this->systemLogger->logException($exception);
			return '';
		}
	}

}