<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Uri;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeSiteResolvingService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Neos\Domain\Service\NodeShortcutResolver;
use Neos\Fusion\ViewHelpers\FusionContextTrait;

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
     * @var NodeShortcutResolver
     */
    protected $nodeShortcutResolver;


    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var NodeSiteResolvingService
     */
    protected $nodeSiteResolvingService;

    /**
     * Initialize arguments
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('subgraph', 'mixed', 'The subgraph');

        $this->registerArgument('node', 'mixed', 'A node object, a string node path (absolute or relative), a string node://-uri or NULL');
        $this->registerArgument('format', 'string', 'Format to use for the URL, for example "html" or "json"');
        $this->registerArgument('absolute', 'boolean', 'If set, an absolute URI is rendered', false, false);
        $this->registerArgument('arguments', 'array', 'Additional arguments to be passed to the UriBuilder (for example pagination parameters)', false, []);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('addQueryString', 'boolean', 'If set, the current query parameters will be kept in the URI', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'arguments to be removed from the URI. Only active if $addQueryString = true', false, []);
        $this->registerArgument('baseNodeName', 'string', 'The name of the base node inside the Fusion context to use for the ContentContext or resolving relative paths', false, 'documentNode');
        $this->registerArgument('nodeVariableName', 'string', 'The variable the node will be assigned to for the rendered child content', false, 'linkedNode');
        $this->registerArgument('resolveShortcuts', 'boolean', 'INTERNAL Parameter - if false, shortcuts are not redirected to their target. Only needed on rare backend occasions when we want to link to the shortcut itself', false, true);
    }

    /**
     * Renders the URI.
     *
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function render()
    {
        $node = $this->arguments['node'];
        $uri = null;
        $nodeAddress = null;


        if ($node instanceof TraversableNodeInterface) {
            // the latter case is only relevant in extremely rare occasions in the Neos Backend, when we want to generate
            // a link towards the *shortcut itself*, and not to its target.
            // TODO: fix shortcuts!
            //$resolvedNode = $resolveShortcuts ? $resolvedNode = $this->nodeShortcutResolver->resolveShortcutTarget($node) : $node;
            $resolvedNode = $node;
            if ($resolvedNode instanceof TraversableNodeInterface) {
                $nodeAddress = $this->nodeAddressFactory->createFromTraversableNode($node);
            } else {
                $uri = $resolvedNode;
            }
        } elseif ($node === '~') {
            /* @var TraversableNodeInterface $documentNode */
            $documentNode = $this->getContextVariable('documentNode');
            $nodeAddress = $this->nodeAddressFactory->createFromTraversableNode($documentNode);
            $siteNode = $this->nodeSiteResolvingService->findSiteNodeForNodeAddress($nodeAddress);
            $nodeAddress = $this->nodeAddressFactory->adjustWithNodeAggregateIdentifier($nodeAddress, $siteNode->getNodeAggregateIdentifier());
        } elseif (is_string($node) && substr($node, 0, 7) === 'node://') {
            /* @var TraversableNodeInterface $documentNode */
            $documentNode = $this->getContextVariable('documentNode');
            $nodeAddress = $this->nodeAddressFactory->createFromTraversableNode($documentNode);
            $nodeAddress = $this->nodeAddressFactory->adjustWithNodeAggregateIdentifier($nodeAddress, NodeAggregateIdentifier::fromString(\mb_substr($node, 7)));
        } else {
            // @todo add path support
            return '';
        }

        if (!$uri) {
            if ($this->arguments['subgraph']) {
                $nodeAddress = $this->nodeAddressFactory->adjustWithDimensionSpacePoint($nodeAddress, $this->arguments['subgraph']->getDimensionSpacePoint());
            }

            $uriBuilder = new UriBuilder();
            $uriBuilder->setRequest($this->controllerContext->getRequest());
            $uriBuilder->setFormat($this->arguments['format'])
                ->setCreateAbsoluteUri($this->arguments['absolute'])
                ->setArguments($this->arguments['arguments'])
                ->setSection($this->arguments['section'])
                ->setAddQueryString($this->arguments['addQueryString'])
                ->setArgumentsToBeExcludedFromQueryString($this->arguments['argumentsToBeExcludedFromQueryString']);

            $uri = $uriBuilder->uriFor(
                'show',
                [
                    'node' => $nodeAddress
                ],
                'Frontend\Node',
                'Neos.Neos'
            );
        }

        return $uri;
    }
}
