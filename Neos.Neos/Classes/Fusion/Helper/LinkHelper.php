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

namespace Neos\Neos\Fusion\Helper;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Neos\EventSourcedRouting\NodeUriBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Fusion\ConvertUrisImplementation;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Eel helper for the linking service
 */
class LinkHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

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
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @param string|Uri $uri
     * @return boolean
     */
    public function hasSupportedScheme($uri): bool
    {
        return in_array($this->getScheme($uri), ['node', 'asset'], true);
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

        if (is_string($uri) && preg_match(ConvertUrisImplementation::PATTERN_SUPPORTED_URIS, $uri, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    public function resolveNodeUri(
        string|Uri $uri,
        NodeInterface $contextNode,
        ControllerContext $controllerContext
    ): ?string {
        $targetNode = $this->convertUriToObject($uri, $contextNode);
        if (!$targetNode instanceof NodeInterface) {
            $this->systemLogger->info(
                sprintf(
                    'Could not resolve "%s" to an existing node; The node was probably deleted.',
                    $uri
                ),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return null;
        }
        $targetNodeAddress = $this->nodeAddressFactory->createFromNode($targetNode);
        try {
            $targetUri = NodeUriBuilder::fromUriBuilder($controllerContext->getUriBuilder())
                ->uriFor($targetNodeAddress);
        } catch (
            NodeAddressCannotBeSerializedException
            | HttpException
            | NoMatchingRouteException
            | MissingActionNameException $e
        ) {
            $this->systemLogger->info(sprintf(
                'Failed to build URI for node "%s": %e',
                $targetNode->getNodeAggregateIdentifier(),
                $e->getMessage()
            ), LogEnvironment::fromMethodName(__METHOD__));
            return null;
        }

        return (string)$targetUri;
    }

    public function resolveAssetUri(string|Uri $uri): string
    {
        if (!$uri instanceof UriInterface) {
            $uri = new Uri($uri);
        }
        $asset = $this->assetRepository->findByIdentifier($uri->getHost());
        if (!$asset instanceof AssetInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Failed to resolve asset from URI "%s", probably the corresponding asset was deleted',
                $uri
            ), 1601373937);
        }

        $assetUri = $this->resourceManager->getPublicPersistentResourceUri($asset->getResource());

        return is_string($assetUri) ? $assetUri : '';
    }

    public function convertUriToObject(
        string|Uri $uri,
        NodeInterface $contextNode = null
    ): NodeInterface|AssetInterface|null {
        if (empty($uri)) {
            return null;
        }
        if ($uri instanceof UriInterface) {
            $uri = (string)$uri;
        }

        if (preg_match(ConvertUrisImplementation::PATTERN_SUPPORTED_URIS, $uri, $matches) === 1) {
            switch ($matches[1]) {
                case 'node':
                    if ($contextNode === null) {
                        throw new \RuntimeException(
                            'node:// URI conversion requires a context node to be passed',
                            1409734235
                        );
                    }
                    $contextNodeAddress = $this->nodeAddressFactory->createFromNode($contextNode);
                    if ($contextNodeAddress->workspaceName === null) {
                        throw new \RuntimeException(sprintf(
                            'Failed to create node address for context node "%s"',
                            $contextNode->getNodeAggregateIdentifier()
                        ));
                    }
                    $visibilityConstraints = $contextNodeAddress->workspaceName->isLive()
                        ? VisibilityConstraints::frontend()
                        : VisibilityConstraints::withoutRestrictions();
                    $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                        $contextNode->getContentStreamIdentifier(),
                        $contextNode->getDimensionSpacePoint(),
                        $visibilityConstraints
                    );

                    return $nodeAccessor->findByIdentifier(
                        NodeAggregateIdentifier::fromString($matches[2])
                    );
                case 'asset':
                    /** @var AssetInterface|null $asset */
                    /** @noinspection OneTimeUseVariablesInspection */
                    $asset = $this->assetRepository->findByIdentifier($matches[2]);
                    return $asset;
            }
        }
        return null;
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
