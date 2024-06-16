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

namespace Neos\Neos\ViewHelpers\Link;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;
use Neos\Fusion\ViewHelpers\FusionContextTrait;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\FrontendRouting\Exception\InvalidShortcutException;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;
use Neos\Neos\FrontendRouting\NodeShortcutResolver;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\Options;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;

/**
 * A view helper for creating links with URIs pointing to nodes.
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
 * <code title="Defaults">
 * <neos:link.node>some link</neos:link.node>
 * </code>
 * <output>
 * <a href="sites/mysite.com/homepage/about.html">some link</a>
 * (depending on current node, format etc.)
 * </output>
 *
 * <code title="Generating a link with an absolute URI">
 * <neos:link.node absolute="{true}">bookmark this page</neos:link.node>
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
 * <code title="Target node given as node://-uri">
 * <neos:link.node node="node://30e893c1-caef-0ca5-b53d-e5699bb8e506">Corporate imprint</neos:link.node>
 * </code>
 * <output>
 * <a href="contact/imprint.html">Corporate imprint</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * <code title="Target node given as relative node path">
 * <neos:link.node node="~/about/us">About us</neos:link.node>
 * </code>
 * <output>
 * <a href="about/us.html">About us</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * <code title="Node label as tag content">
 * <neos:link.node node="/sites/exampleorg/contact/imprint" />
 * </code>
 * <output>
 * <a href="contact/imprint.html">Imprint</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * <code title="Dynamic tag content involving the linked node's properties">
 * <neos:link.node node="about-us">see our <span>{linkedNode.label}</span> page</neos:link.node>
 * </code>
 * <output>
 * <a href="about-us.html">see our <span>About Us</span> page</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * @api
 */
class NodeViewHelper extends AbstractTagBasedViewHelper
{
    use FusionContextTrait;
    use NodeTypeWithFallbackProvider;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * @Flow\Inject
     * @var NodeShortcutResolver
     */
    protected $nodeShortcutResolver;

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
     * @var NodeLabelGeneratorInterface
     */
    protected $nodeLabelGenerator;

    /**
     * Initialize arguments
     *
     * @return void
     * @throws ViewHelperException
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute(
            'name',
            'string',
            'Specifies the name of an anchor'
        );
        $this->registerTagAttribute(
            'rel',
            'string',
            'Specifies the relationship between the current document and the linked document'
        );
        $this->registerTagAttribute(
            'rev',
            'string',
            'Specifies the relationship between the linked document and the current document'
        );
        $this->registerTagAttribute(
            'target',
            'string',
            'Specifies where to open the linked document'
        );

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
     * Renders the link. Renders the linked node's label if there's no child content.
     *
     * @return string The rendered link
     * @throws ViewHelperException
     */
    public function render(): string
    {
        $node = $this->arguments['node'];
        if (!$node instanceof Node) {
            $node = $this->getContextVariable($this->arguments['baseNodeName']);
        }

        if ($node instanceof Node) {
            $nodeAddress = NodeAddress::fromNode($node);
        } elseif (is_string($node)) {
            $documentNode = $this->getContextVariable('documentNode');
            assert($documentNode instanceof Node);
            $nodeAddress = $this->resolveNodeAddressFromString($node, $documentNode);
            $node = $documentNode;
        } else {
            throw new ViewHelperException(sprintf(
                'The "node" argument can only be a string or an instance of %s. Given: %s',
                Node::class,
                is_object($node) ? get_class($node) : gettype($node)
            ), 1601372376);
        }

        $contentRepository = $this->contentRepositoryRegistry->get($nodeAddress->contentRepositoryId);
        $subgraph = $contentRepository->getContentGraph($nodeAddress->workspaceName)
            ->getSubgraph(
                $nodeAddress->dimensionSpacePoint,
                $node->visibilityConstraints
            );

        $resolvedNode = $subgraph->findNodeById($nodeAddress->aggregateId);
        if ($resolvedNode === null) {
            $this->throwableStorage->logThrowable(new ViewHelperException(sprintf(
                'Failed to resolve node "%s" in workspace "%s" and dimension %s',
                $nodeAddress->aggregateId->value,
                $subgraph->getWorkspaceName()->value,
                $subgraph->getDimensionSpacePoint()->toJson()
            ), 1601372444));
        }
        if ($resolvedNode && $this->getNodeType($resolvedNode)->isOfType(NodeTypeNameFactory::NAME_SHORTCUT)) {
            try {
                $shortcutNodeAddress = $this->nodeShortcutResolver->resolveShortcutTarget(
                    $nodeAddress
                );
                if ($shortcutNodeAddress instanceof NodeAddress) {
                    $resolvedNode = $subgraph
                        ->findNodeById($shortcutNodeAddress->aggregateId);
                }
            } catch (NodeNotFoundException | InvalidShortcutException $e) {
                $this->throwableStorage->logThrowable(new ViewHelperException(sprintf(
                    'Failed to resolve shortcut node "%s" in workspace "%s" and dimension %s',
                    $resolvedNode->aggregateId->value,
                    $subgraph->getWorkspaceName()->value,
                    $subgraph->getDimensionSpacePoint()->toJson()
                ), 1601370239, $e));
            }
        }

        $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest($this->controllerContext->getRequest());

        $options = Options::create(forceAbsolute: $this->arguments['absolute']);
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
        $this->tag->addAttribute('href', (string)$uri);

        $this->templateVariableContainer->add($this->arguments['nodeVariableName'], $resolvedNode);
        $content = $this->renderChildren();
        $this->templateVariableContainer->remove($this->arguments['nodeVariableName']);

        if ($content === null && $resolvedNode !== null) {
            $content = $this->nodeLabelGenerator->getLabel($resolvedNode);
        }

        $this->tag->setContent($content);
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }

    /**
     * Converts strings like "relative/path", "/absolute/path", "~/site-relative/path"
     * and "~" to the corresponding NodeAddress
     *
     * @param string $path
     * @throws ViewHelperException
     */
    private function resolveNodeAddressFromString(string $path, Node $documentNode): NodeAddress
    {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $documentNode->contentRepositoryId
        );
        $documentNodeAddress = NodeAddress::fromNode($documentNode);
        if (strncmp($path, 'node://', 7) === 0) {
            return $documentNodeAddress->withAggregateId(
                NodeAggregateId::fromString(\mb_substr($path, 7))
            );
        }
        $subgraph = $contentRepository->getContentGraph($documentNodeAddress->workspaceName)->getSubgraph(
            $documentNodeAddress->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        if (strncmp($path, '~', 1) === 0) {
            $siteNode = $subgraph->findClosestNode($documentNodeAddress->aggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));
            if ($siteNode === null) {
                throw new ViewHelperException(sprintf(
                    'Failed to determine site node for aggregate node "%s" in workspace "%s" and dimension %s',
                    $documentNodeAddress->aggregateId->value,
                    $subgraph->getWorkspaceName()->value,
                    $subgraph->getDimensionSpacePoint()->toJson()
                ), 1601366598);
            }
            if ($path === '~') {
                $targetNode = $siteNode;
            } else {
                $targetNode = $subgraph->findNodeByPath(
                    NodePath::fromString(substr($path, 1)),
                    $siteNode->aggregateId
                );
            }
        } else {
            $targetNode = $subgraph->findNodeByPath(
                NodePath::fromString($path),
                $documentNode->aggregateId
            );
        }
        if ($targetNode === null) {
            throw new ViewHelperException(sprintf(
                'Node on path "%s" could not be found for aggregate node "%s" in workspace "%s" and dimension %s',
                $path,
                $documentNodeAddress->aggregateId->value,
                $subgraph->getWorkspaceName()->value,
                $subgraph->getDimensionSpacePoint()->toJson()
            ), 1601311789);
        }
        return $documentNodeAddress->withAggregateId($targetNode->aggregateId);
    }
}
