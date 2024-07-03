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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Http\Helper\UriHelper;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Exception as NeosException;
use Neos\Neos\FrontendRouting\NodeUriBuilder;
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
 * @deprecated with Neos 9. Please use the new {@see NodeUriBuilder} instead and for resolving a relative node path {@see NodeAddressNormalizer::resolveNodeAddressFromPath()}
 * @Flow\Scope("singleton")
 */
class LinkingService
{
    /**
     * Pattern to match supported URIs.
     *
     * @var string
     */
    public const PATTERN_SUPPORTED_URIS
        = '/(node|asset):\/\/([a-z0-9\-]+|([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12})/';

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

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

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @param string|UriInterface $uri
     * @return boolean
     */
    public function hasSupportedScheme($uri): bool
    {
        if ($uri instanceof UriInterface) {
            $uri = (string)$uri;
        }

        return $uri !== null && preg_match(self::PATTERN_SUPPORTED_URIS, $uri) === 1;
    }

    /**
     * @param string|UriInterface $uri
     * @return string
     */
    public function getScheme($uri): string
    {
        if ($uri instanceof UriInterface) {
            return $uri->getScheme();
        }

        if ($uri !== null && preg_match(self::PATTERN_SUPPORTED_URIS, $uri, $matches) === 1) {
            return $matches[1];
        }

        return '';
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
     */
    public function resolveNodeUri(
        string $uri,
        Node $contextNode,
        ControllerContext $controllerContext,
        bool $absolute = false
    ): ?string {
        return $this->createNodeUri($controllerContext, $uri, $contextNode, null, $absolute);
    }

    /**
     * Resolves a given asset:// URI to a "normal" HTTP(S) URI for the addressed asset's resource.
     *
     * @param string $uri
     * @return string|null If the URI cannot be resolved, null is returned
     */
    public function resolveAssetUri(string $uri): ?string
    {
        $targetObject = $this->convertUriToObject($uri);
        if (!$targetObject instanceof Asset) {
            $this->systemLogger->info(
                sprintf('Could not resolve "%s" to an existing asset; The asset was probably deleted.', $uri),
                LogEnvironment::fromMethodName(__METHOD__)
            );

            return null;
        }

        $assetUri = $this->resourceManager->getPublicPersistentResourceUri($targetObject->getResource());

        return is_string($assetUri)
            ? $assetUri
            : null;
    }

    /**
     * Return the object the URI addresses or NULL.
     *
     * @param string|UriInterface $uri
     * @param Node $contextNode
     * @return Node|AssetInterface|NULL
     */
    public function convertUriToObject($uri, Node $contextNode = null)
    {
        if ($uri instanceof UriInterface) {
            $uri = (string)$uri;
        }

        if (preg_match(self::PATTERN_SUPPORTED_URIS, $uri, $matches) === 1) {
            switch ($matches[1]) {
                case 'node':
                    if (!$contextNode instanceof Node) {
                        throw new \RuntimeException(
                            'node:// URI conversion requires a context node to be passed',
                            1409734235
                        );
                    }
                    return $this->contentRepositoryRegistry->subgraphForNode($contextNode)
                        ->findNodeById(
                            NodeAggregateId::fromString($matches[2])
                        );
                case 'asset':
                    /** @var ?AssetInterface $asset */
                    $asset = $this->assetRepository->findByIdentifier($matches[2]);

                    return $asset;
                default:
            }
        }

        return null;
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
     */
    public function getLastLinkedNode(): ?Node
    {
        return $this->lastLinkedNode;
    }
}
