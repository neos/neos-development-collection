<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\ViewHelpers\Uri;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;
use Neos\Fusion\ViewHelpers\FusionContextTrait;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\Options;
use Neos\Neos\Utility\LegacyNodePathNormalizer;
use Neos\Neos\Utility\NodePathResolver;

/**
 * A view helper for creating URIs pointing to nodes.
 *
 * The target node can be provided as string or as a Node object; if not specified
 * at all, the generated URI will refer to the current document node inside the Fusion context.
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
 *
 * <code title="Target node given as node://-uri">
 * <neos:uri.node node="node://30e893c1-caef-0ca5-b53d-e5699bb8e506" />
 * </code>
 * <output>
 * about/us.html
 * (depending on current workspace, current node, format etc.)
 * </output>
 * @api
 */
class NodeViewHelper extends AbstractViewHelper
{
    use FusionContextTrait;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @Flow\Inject
     * @var NodeUriBuilderFactory
     */
    protected $nodeUriBuilderFactory;

    /**
     * @Flow\Inject
     * @var NodePathResolver
     */
    protected $nodePathResolver;

    /**
     * @Flow\Inject
     * @var LegacyNodePathNormalizer
     */
    protected $legacyNodePathNormalizer;

    /**
     * Initialize arguments
     *
     * @return void
     * @throws ViewHelperException
     */
    public function initializeArguments()
    {
        $this->registerArgument(
            'node',
            'mixed',
            'A node object, a string node path (absolute or relative), a string node://-uri or NULL'
        );
        $this->registerArgument(
            'format',
            'string',
            'Format to use for the URL, for example "html" or "json"'
        );
        $this->registerArgument(
            'absolute',
            'boolean',
            'If set, an absolute URI is rendered',
            false,
            false
        );
        $this->registerArgument(
            'arguments',
            'array',
            'Additional arguments to be passed to the UriBuilder (for example pagination parameters)',
            false,
            []
        );
        $this->registerArgument(
            'section',
            'string',
            'The anchor to be added to the URI',
            false,
            ''
        );
        $this->registerArgument(
            'baseNodeName',
            'string',
            'The name of the base node inside the Fusion context to use for the ContentContext'
            . ' or resolving relative paths',
            false,
            'documentNode'
        );
        $this->registerArgument(
            'nodeVariableName',
            'string',
            'The variable the node will be assigned to for the rendered child content',
            false,
            'linkedNode'
        );
    }

    /**
     * Renders the URI.
     */
    public function render(): string
    {
        $node = $this->arguments['node'];
        if (is_string($node)) {
            $baseNode = $this->getContextVariable($this->arguments['baseNodeName']);
            if (!$baseNode instanceof Node) {
                throw new ViewHelperException(sprintf(
                    'If "node" is passed as string a base node in must be set in "%s". Given: %s',
                    $this->arguments['baseNodeName'],
                    get_debug_type($baseNode)
                ), 1719953186);
            }

            if (str_starts_with($node, 'node://')) {
                $nodeAddress = NodeAddress::fromNode($baseNode)->withAggregateId(
                    NodeAggregateId::fromString(substr($node, strlen('node://')))
                );
            } else {
                $possibleAbsoluteNodePath = $this->legacyNodePathNormalizer->tryResolveLegacyPathSyntaxToAbsoluteNodePath($node, $baseNode);
                $nodeAddress = $this->nodePathResolver->resolveNodeAddressByPath(
                    $possibleAbsoluteNodePath ?? $node,
                    $baseNode
                );
            }
        } elseif ($node instanceof Node) {
            $nodeAddress = NodeAddress::fromNode($node);
        } else {
            throw new ViewHelperException(sprintf(
                'The "node" argument can only be a string or an instance of `Node`. Given: %s',
                get_debug_type($node)
            ), 1601372376);
        }

        $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest($this->controllerContext->getRequest());

        $options = $this->arguments['absolute'] ? Options::createForceAbsolute() : Options::createEmpty();
        $format = $this->arguments['format'] ?: $this->controllerContext->getRequest()->getFormat();
        if ($format && $format !== 'html') {
            $options = $options->withCustomFormat($format);
        }
        if ($routingArguments = $this->arguments['arguments']) {
            $options = $options->withCustomRoutingArguments($routingArguments);
        }

        $uri = '';
        try {
            $uri = $nodeUriBuilder->uriFor($nodeAddress, $options);

            if ($this->arguments['section'] !== '') {
                $uri = $uri->withFragment($this->arguments['section']);
            }
        } catch (NoMatchingRouteException $e) {
            $this->throwableStorage->logThrowable(new ViewHelperException(sprintf(
                'Failed to build URI for node: %s: %s',
                $nodeAddress->toJson(),
                $e->getMessage()
            ), 1601372594, $e));
        }
        return (string)$uri;
    }
}
