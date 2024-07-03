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

namespace Neos\Neos\Service;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Http\Helper\UriHelper;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Exception as NeosException;
use Neos\Neos\FrontendRouting\NodeUriBuilder;
use Neos\Neos\Fusion\Helper\LinkHelper;
use Neos\Neos\Utility\LegacyNodePathNormalizer;
use Neos\Neos\Utility\NodeAddressNormalizer;
use Neos\Utility\Arrays;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * A service for creating URIs pointing to nodes and assets.
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
 * @deprecated with Neos 9. Please use the new {@see NodeUriBuilder} instead and for resolving a relative node path {@see NodeAddressNormalizer::resolveNodeAddressFromPath()} or utilize the {@see LinkHelper} from Fusion
 * @Flow\Scope("singleton")
 */
class LinkingService
{
    protected ?Node $lastLinkedNode;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var BaseUriProvider
     */
    protected $baseUriProvider;

    /**
     * @Flow\Inject
     * @var NodeAddressNormalizer
     */
    protected $nodeAddressNormalizer;

    /**
     * @Flow\Inject
     * @var LegacyNodePathNormalizer
     */
    protected $legacyNodePathNormalizer;

    /**
     * @Flow\Inject
     * @var LinkHelper
     */
    protected $newLinkHelper;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @param string|UriInterface $uri
     * @return boolean
     * @deprecated with Neos 9
     */
    public function hasSupportedScheme($uri): bool
    {
        return $this->newLinkHelper->hasSupportedScheme($uri);
    }

    /**
     * @param string|UriInterface $uri
     * @return string
     * @deprecated with Neos 9
     */
    public function getScheme($uri): string
    {
        return $this->newLinkHelper->getScheme($uri) ?? '';
    }

    /**
     * Resolves a given node:// URI to a "normal" HTTP(S) URI for the addressed node.
     *
     * @param string $uri
     * @param Node $contextNode
     * @param ControllerContext $controllerContext
     * @param bool $absolute
     * @return string|null If the node cannot be resolved, null is returned
     * @throws NeosException
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @deprecated with Neos 9
     */
    public function resolveNodeUri(
        string $uri,
        Node $contextNode,
        ControllerContext $controllerContext,
        bool $absolute = false
    ): ?string {
        try {
            if ($this->newLinkHelper->getScheme($uri) !== 'node') {
                throw new \RuntimeException(sprintf(
                    'Invalid node uri "%s" provided. It must start with node://',
                    $uri
                ), 1720004437);
            }
            return $this->createNodeUri($controllerContext, $uri, $contextNode, null, $absolute);
        } catch (\RuntimeException $e) {
            $this->systemLogger->info(
                sprintf('Could not resolve "%s" to an existing node; %s', $uri, $e->getMessage()),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return null;
        }
    }

    /**
     * Resolves a given asset:// URI to a "normal" HTTP(S) URI for the addressed asset's resource.
     *
     * @param string $uri
     * @return string|null If the URI cannot be resolved, null is returned
     * @deprecated with Neos 9
     */
    public function resolveAssetUri(string $uri): ?string
    {
        try {
            return $this->newLinkHelper->resolveAssetUri($uri);
        } catch (\RuntimeException $e) {
            $this->systemLogger->info(
                sprintf('Could not resolve "%s" to an existing asset; %s', $uri, $e->getMessage()),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return null;
        }
    }

    /**
     * Return the object the URI addresses or NULL.
     *
     * @param string|UriInterface $uri
     * @param Node $contextNode
     * @return Node|AssetInterface|NULL
     * @deprecated with Neos 9
     */
    public function convertUriToObject($uri, Node $contextNode = null)
    {
        return $this->newLinkHelper->convertUriToObject($uri, $contextNode);
    }

    /**
     * Renders the URI to a given node instance or -path.
     *
     * @param ControllerContext $controllerContext
     * @param Node|string|null $node A node object or a string node path,
     *                    if a relative path is provided the baseNode argument is required
     * @param Node|null $baseNode
     * @param string $format Format to use for the URL, for example "html" or "json"
     * @param boolean $absolute If set, an absolute URI is rendered
     * @param array<string,mixed> $arguments Additional arguments to be passed to the UriBuilder
     *                                       (e.g. pagination parameters)
     * @param string $section
     * @param boolean $addQueryString If set, the current query parameters will be kept in the URI @deprecated see https://github.com/neos/neos-development-collection/issues/5076
     * @param array<int,string> $argumentsToBeExcludedFromQueryString arguments to be removed from the URI.
     *                                                    Only active if $addQueryString = true @deprecated see https://github.com/neos/neos-development-collection/issues/5076
     * @param boolean $resolveShortcuts @deprecated With Neos 7.0 this argument is no longer evaluated
     *                                  and log a message if set to FALSE
     * @return string The rendered URI
     * @throws NeosException if no URI could be resolved for the given node
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @throws HttpException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @deprecated with Neos 9
     */
    public function createNodeUri(
        ControllerContext $controllerContext,
        $node = null,
        Node $baseNode = null,
        $format = null,
        $absolute = false,
        array $arguments = [],
        $section = '',
        $addQueryString = false,
        array $argumentsToBeExcludedFromQueryString = [],
        $resolveShortcuts = true
    ): string {
        $this->lastLinkedNode = null;
        if ($resolveShortcuts === false) {
            $this->systemLogger->info(sprintf(
                '%s() was called with the "resolveShortCuts" argument set to FALSE.'
                    . ' This is no longer supported, the argument was ignored',
                __METHOD__
            ));
        }

        $resolvedNode = null;
        if (is_string($node)) {
            if (!$baseNode instanceof Node) {
                throw new \RuntimeException('If "node" is passed as string a base node in must be given', 1719999788);
            }

            $possibleAbsoluteNodePath = $this->legacyNodePathNormalizer->tryResolveLegacyPathSyntaxToAbsoluteNodePath($node, $baseNode);
            $nodeAddress = $this->nodeAddressNormalizer->resolveNodeAddressFromPath(
                $possibleAbsoluteNodePath ?? $node,
                $baseNode
            );

            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($baseNode);
            $resolvedNode = $subgraph->findNodeById($nodeAddress->aggregateId);
            if ($resolvedNode === null) {
                throw new \RuntimeException(sprintf(
                    'Failed to resolve node "%s" (path %s) in workspace "%s" and dimension %s',
                    $nodeAddress->aggregateId->value,
                    $node,
                    $subgraph->getWorkspaceName()->value,
                    $subgraph->getDimensionSpacePoint()->toJson()
                ), 1720000002);
            }
        } elseif ($node instanceof Node) {
            $nodeAddress = NodeAddress::fromNode($node);
            $resolvedNode = $node;
        } elseif ($node === null) {
            if (!$baseNode instanceof Node) {
                throw new \RuntimeException('If "node" is is NULL a base node in must be given', 1719999803);
            }
            $nodeAddress = NodeAddress::fromNode($baseNode);
            $resolvedNode = $baseNode;
        } else {
            throw new \RuntimeException(sprintf(
                'The "node" argument can only be a string or an instance of `Node`. Given: %s',
                get_debug_type($node)
            ), 1601372376);
        }

        $this->lastLinkedNode = $resolvedNode;

        $contentRepository = $this->contentRepositoryRegistry->get($nodeAddress->contentRepositoryId);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($nodeAddress->workspaceName);

        $mainRequest = $controllerContext->getRequest()->getMainRequest();
        $uriBuilder = clone $controllerContext->getUriBuilder();
        $uriBuilder->setRequest($mainRequest);
        // todo why do we evaluate the hidden state here? We dont do it in the new uri builder.
        $createLiveUri = $workspace && $workspace->isPublicWorkspace() && $resolvedNode->tags->contain(SubtreeTag::disabled());

        if ($addQueryString === true) {
            // legacy feature see https://github.com/neos/neos-development-collection/issues/5076
            $requestArguments = $mainRequest->getArguments();
            foreach ($argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                unset($requestArguments[$argumentToBeExcluded]);
            }
            if ($requestArguments !== []) {
                $arguments = Arrays::arrayMergeRecursiveOverrule($requestArguments, $arguments);
            }
        }

        if (!$createLiveUri) {
            $previewActionUri = $uriBuilder
                ->reset()
                ->setSection($section)
                ->setArguments($arguments)
                ->setFormat($format ?: $mainRequest->getFormat())
                ->setCreateAbsoluteUri($absolute)
                ->uriFor('preview', [], 'Frontend\Node', 'Neos.Neos');
            return (string)UriHelper::uriWithAdditionalQueryParameters(
                new Uri($previewActionUri),
                ['node' => $nodeAddress->toJson()]
            );
        }

        return $uriBuilder
            ->reset()
            ->setSection($section)
            ->setArguments($arguments)
            ->setFormat($format ?: $mainRequest->getFormat())
            ->setCreateAbsoluteUri($absolute)
            ->uriFor('show', ['node' => $nodeAddress], 'Frontend\Node', 'Neos.Neos');
    }

    /**
     * @param ControllerContext $controllerContext
     * @param Site $site
     * @return string
     * @throws NeosException
     * @throws HttpException
     * @deprecated with Neos 9 - todo find alternative
     */
    public function createSiteUri(ControllerContext $controllerContext, Site $site): string
    {
        $primaryDomain = $site->getPrimaryDomain();
        if ($primaryDomain === null) {
            throw new NeosException(sprintf(
                'Cannot link to a site "%s" since it has no active domains.',
                $site->getName()
            ), 1460443524);
        }
        $httpRequest = $controllerContext->getRequest()->getHttpRequest();
        $requestUri = $httpRequest->getUri();
        // TODO: Should probably directly use \Neos\Flow\Http\Helper\RequestInformationHelper::getRelativeRequestPath()
        // and even that is tricky.
        $baseUri = $this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest($httpRequest);
        $port = $primaryDomain->getPort() ?: $requestUri->getPort();
        return sprintf(
            '%s://%s%s%s',
            $primaryDomain->getScheme() ?: $requestUri->getScheme(),
            $primaryDomain->getHostname(),
            $port && !in_array($port, [80, 443], true) ? ':' . $port : '',
            rtrim($baseUri->getPath(), '/') // remove trailing slash, $uri has leading slash already
        );
    }

    /**
     * Returns the node that was last used to resolve a link to.
     * May return NULL in case no link has been generated or an error occurred on the last linking run.
     *
     * @return Node
     * @deprecated with Neos 9
     */
    public function getLastLinkedNode(): ?Node
    {
        return $this->lastLinkedNode;
    }
}
