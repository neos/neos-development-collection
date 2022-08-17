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

use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\Projection\NodeHiddenState\NodeHiddenStateProjection;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\NodeShortcutResolver;
use Neos\Neos\Exception as NeosException;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
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
 * ``../about`` results in ``/sites/acmecom/about/``,
 * ``./neos/info`` results in ``/sites/acmecom/products/neos/info``.
 *
 * *``node`` starts with a tilde character (``~``):*
 * The given path is treated as a path relative to the current site node.
 * Example: given that the current node is ``/sites/acmecom/products/``,
 * ``~/about/us`` results in ``/sites/acmecom/about/us``,
 * ``~`` results in ``/sites/acmecom``.
 *
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
     * @var NodeShortcutResolver
     */
    protected $nodeShortcutResolver;

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

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

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

        return preg_match(self::PATTERN_SUPPORTED_URIS, $uri) === 1;
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

        if (preg_match(self::PATTERN_SUPPORTED_URIS, $uri, $matches) === 1) {
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
        $targetObject = $this->convertUriToObject($uri, $contextNode);
        if ($targetObject === null) {
            $this->systemLogger->info(
                sprintf('Could not resolve "%s" to an existing node; The node was probably deleted.', $uri),
                LogEnvironment::fromMethodName(__METHOD__)
            );

            return null;
        }

        return $this->createNodeUri($controllerContext, $targetObject, null, null, $absolute);
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
                        ->findNodeByNodeAggregateIdentifier(
                            NodeAggregateIdentifier::fromString($matches[2])
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
     * @param mixed $node A node object or a string node path,
     *                    if a relative path is provided the baseNode argument is required
     * @param Node $baseNode
     * @param string $format Format to use for the URL, for example "html" or "json"
     * @param boolean $absolute If set, an absolute URI is rendered
     * @param array<string,mixed> $arguments Additional arguments to be passed to the UriBuilder
     *                                       (e.g. pagination parameters)
     * @param string $section
     * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
     * @param array<int,string> $argumentsToBeExcludedFromQueryString arguments to be removed from the URI.
     *                                                    Only active if $addQueryString = true
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
        if (!($node instanceof Node || is_string($node) || $baseNode instanceof Node)) {
            throw new \InvalidArgumentException(
                'Expected an instance of Node or a string for the node argument,'
                    . ' or alternatively a baseNode argument.',
                1373101025
            );
        }

        if (is_string($node)) {
            $nodeString = $node;
            if ($nodeString === '') {
                throw new NeosException(sprintf('Empty strings can not be resolved to nodes.'), 1415709942);
            }
            try {
                // if we get a node string, we need to assume it links to the current site
                $contentRepositoryIdentifier = SiteDetectionResult::fromRequest(
                    $controllerContext->getRequest()->getHttpRequest()
                )->contentRepositoryIdentifier;
                $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);
                $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromUriString($node);
                $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($nodeAddress->workspaceName);
                $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                    $nodeAddress->contentStreamIdentifier,
                    $nodeAddress->dimensionSpacePoint,
                    $workspace && !$workspace->isPublicWorkspace()
                        ? VisibilityConstraints::withoutRestrictions()
                        : VisibilityConstraints::frontend()
                );
                $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->nodeAggregateIdentifier);
            } catch (\Throwable $exception) {
                if ($baseNode === null) {
                    throw new NeosException(
                        'The baseNode argument is required for linking to nodes with a relative path.',
                        1407879905
                    );
                }
                $node = $this->contentRepositoryRegistry->subgraphForNode($baseNode)
                    ->findNodeByPath(NodePath::fromString($nodeString), $baseNode->nodeAggregateIdentifier);
            }
            if (!$node instanceof Node) {
                throw new NeosException(sprintf(
                    'The string "%s" could not be resolved to an existing node.',
                    $nodeString
                ), 1415709674);
            }
        } elseif (!$node instanceof Node) {
            $node = $baseNode;
        }

        if (!$node instanceof Node) {
            throw new NeosException(sprintf(
                'Node must be an instance of Node or string, given "%s".',
                gettype($node)
            ), 1414772029);
        }
        $this->lastLinkedNode = $node;

        $contentRepository = $this->contentRepositoryRegistry->get(
            $node->subgraphIdentity->contentRepositoryIdentifier
        );
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamIdentifier(
            $node->subgraphIdentity->contentStreamIdentifier
        );
        $nodeHiddenStateFinder = $contentRepository->projectionState(NodeHiddenStateProjection::class);
        $hiddenState = $nodeHiddenStateFinder->findHiddenState(
            $node->subgraphIdentity->contentStreamIdentifier,
            $node->subgraphIdentity->dimensionSpacePoint,
            $node->nodeAggregateIdentifier
        );

        $request = $controllerContext->getRequest()->getMainRequest();
        $uriBuilder = clone $controllerContext->getUriBuilder();
        $uriBuilder->setRequest($request);
        $action = $workspace && $workspace->isPublicWorkspace() && !$hiddenState->isHidden() ? 'show' : 'preview';

        return $uriBuilder
            ->reset()
            ->setSection($section)
            ->setArguments($arguments)
            ->setAddQueryString($addQueryString)
            ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
            ->setFormat($format ?: $request->getFormat())
            ->setCreateAbsoluteUri($absolute)
            ->uriFor($action, ['node' => $node], 'Frontend\Node', 'Neos.Neos');
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
