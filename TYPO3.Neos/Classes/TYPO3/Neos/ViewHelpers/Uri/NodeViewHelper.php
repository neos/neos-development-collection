<?php
namespace TYPO3\Neos\ViewHelpers\Uri;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Exception\NoMatchingRouteException;
use TYPO3\Neos\Exception as NeosException;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Neos\Service\LinkingService;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\ViewHelpers\TypoScriptContextTrait;

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
class NodeViewHelper extends AbstractViewHelper
{
    use TypoScriptContextTrait;

    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * Renders the URI.
     *
     * @param mixed $node A node object or a string node path (absolute or relative) a string node://-uri or NULL to resolve the current document node
     * @param string $format Format to use for the URL, for example "html" or "json"
     * @param boolean $absolute If set, an absolute URI is rendered
     * @param array $arguments Additional arguments to be passed to the UriBuilder (for example pagination parameters)
     * @param string $section
     * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
     * @param string $baseNodeName The name of the base node inside the TypoScript context to use for the ContentContext or resolving relative paths
     * @param boolean $resolveShortcuts INTERNAL Parameter - if FALSE, shortcuts are not redirected to their target. Only needed on rare backend occasions when we want to link to the shortcut itself.
     * @return string The rendered URI or NULL if no URI could be resolved for the given node
     * @throws ViewHelperException
     */
    public function render($node = null, $format = null, $absolute = false, array $arguments = array(), $section = '', $addQueryString = false, array $argumentsToBeExcludedFromQueryString = array(), $baseNodeName = 'documentNode', $resolveShortcuts = true)
    {
        $baseNode = null;
        if (!$node instanceof NodeInterface) {
            $baseNode = $this->getContextVariable($baseNodeName);
            if (is_string($node) && substr($node, 0, 7) === 'node://') {
                $node = $this->linkingService->convertUriToObject($node, $baseNode);
            }
        }

        try {
            return $this->linkingService->createNodeUri(
                $this->controllerContext,
                $node,
                $baseNode,
                $format,
                $absolute,
                $arguments,
                $section,
                $addQueryString,
                $argumentsToBeExcludedFromQueryString,
                $resolveShortcuts
            );
        } catch (NeosException $exception) {
            $this->systemLogger->logException($exception);
        } catch (NoMatchingRouteException $exception) {
            $this->systemLogger->logException($exception);
        }
        return '';
    }
}
