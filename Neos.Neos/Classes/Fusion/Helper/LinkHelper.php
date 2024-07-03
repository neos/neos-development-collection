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
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\Service\LinkingService;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

class LinkHelper implements ProtectedContextAwareInterface
{
    private const NODE_SCHEME = 'node';
    private const ASSET_SCHEME = 'asset';

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
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var NodeUriBuilderFactory
     */
    protected $nodeUriBuilderFactory;

    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @param string|Uri $uri
     * @return boolean
     */
    public function hasSupportedScheme($uri): bool
    {
        $scheme = $this->getScheme($uri);
        return $scheme === self::NODE_SCHEME || $scheme === self::ASSET_SCHEME;
    }

    /**
     * @param string|UriInterface $uri
     */
    public function getScheme($uri): ?string
    {
        if ($uri === null || $uri === '') {
            return null;
        }
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }
        return $uri->getScheme();
    }

    /**
     * @param string|UriInterface $uri
     * @param Node $contextNode
     * @param ControllerContext $controllerContext
     * @return string
     * @deprecated with Neos 9 as the linking service is deprecated and this helper cannot be invoked from Fusion either way as the $controllerContext is not available.
     */
    public function resolveNodeUri(string|UriInterface $uri, Node $contextNode, ControllerContext $controllerContext)
    {
        return $this->linkingService->resolveNodeUri($uri, $contextNode, $controllerContext);
    }

    public function resolveAssetUri(string|Uri $uri): string
    {
        if (!$uri instanceof UriInterface) {
            $uri = new Uri($uri);
        }
        if ($uri->getScheme() !== self::ASSET_SCHEME) {
            throw new \RuntimeException(sprintf(
                'Invalid asset uri "%s" provided. It must start with asset://',
                $uri
            ), 1720003716);
        }

        $asset = $this->assetRepository->findByIdentifier($uri->getHost());
        if (!$asset instanceof AssetInterface) {
            throw new \RuntimeException(sprintf(
                'Failed to resolve asset from URI "%s", probably the corresponding asset was deleted',
                $uri
            ), 1601373937);
        }

        $assetUri = $this->resourceManager->getPublicPersistentResourceUri($asset->getResource());

        return is_string($assetUri) ? $assetUri : '';
    }

    public function convertUriToObject(
        string|Uri $uri,
        Node $contextNode = null
    ): Node|AssetInterface|null {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }
        switch ($uri->getScheme()) {
            case self::NODE_SCHEME:
                if ($contextNode === null) {
                    throw new \RuntimeException(
                        sprintf('node:// URI conversion like "%s" requires a context node to be passed', $uri),
                        1409734235
                    );
                }
                return $this->contentRepositoryRegistry->subgraphForNode($contextNode)
                    ->findNodeById(NodeAggregateId::fromString($uri->getHost()));
            case self::ASSET_SCHEME:
                /** @var AssetInterface|null $asset */
                $asset = $this->assetRepository->findByIdentifier($uri->getHost());
                return $asset;
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
