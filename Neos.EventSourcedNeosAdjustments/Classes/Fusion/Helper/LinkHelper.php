<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\Exception\NodeAddressCannotBeSerializedException;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\NodeUriBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
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
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

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
     * @var ContentGraphInterface
     */
    protected $contentGraph;

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
        if (!$uri instanceof UriInterface) {
            $uri = new Uri($uri);
        }
        return $uri->getScheme();
    }

    /**
     * @param string|Uri $uri
     * @param TraversableNodeInterface $contextNode
     * @param ControllerContext $controllerContext
     * @return string
     */
    public function resolveNodeUri($uri, TraversableNodeInterface $contextNode, ControllerContext $controllerContext): ?string
    {
        $targetNode = $this->convertUriToObject($uri, $contextNode);
        if (!$targetNode instanceof TraversableNodeInterface) {
            $this->systemLogger->info(sprintf('Could not resolve "%s" to an existing node; The node was probably deleted.', $uri), LogEnvironment::fromMethodName(__METHOD__));
            return null;
        }
        $targetNodeAddress = $this->nodeAddressFactory->createFromTraversableNode($targetNode);
        if ($targetNodeAddress === null) {
            $this->systemLogger->info(sprintf('Could not create node address from node "%s".', $targetNode->getNodeAggregateIdentifier()), LogEnvironment::fromMethodName(__METHOD__));
            return null;
        }
        try {
            $targetUri = NodeUriBuilder::fromUriBuilder($controllerContext->getUriBuilder())->uriFor($targetNodeAddress);
        } catch (NodeAddressCannotBeSerializedException | HttpException | NoMatchingRouteException | MissingActionNameException $e) {
            $this->systemLogger->info(sprintf('Failed to build URI for node "%s": %e', $targetNode->getNodeAggregateIdentifier(), $e->getMessage()), LogEnvironment::fromMethodName(__METHOD__));
            return null;
        }
        if ($targetUri === null) {
            return null;
        }
        return (string)$targetUri;
    }

    /**
     * @param string|Uri $uri
     * @return string
     */
    public function resolveAssetUri($uri): string
    {
        if (!$uri instanceof UriInterface) {
            $uri = new Uri($uri);
        }
        $asset = $this->assetRepository->findByIdentifier($uri->getHost());
        if ($asset === null) {
            throw new \InvalidArgumentException(sprintf('Failed to resolve asset from URI "%s", probably the corresponding asset was deleted', $uri), 1601373937);
        }
        return $this->resourceManager->getPublicPersistentResourceUri($asset->getResource());
    }

    /**
     * @param string|Uri $uri
     * @param TraversableNodeInterface|null $contextNode
     * @return TraversableNodeInterface|AssetInterface|NULL
     */
    public function convertUriToObject($uri, TraversableNodeInterface $contextNode = null)
    {
        if (empty($uri)) {
            return null;
        }
        if (!$uri instanceof UriInterface) {
            $uri = new Uri($uri);
        }

        switch ($uri->getScheme()) {
            case 'node':
                if ($contextNode === null) {
                    throw new \RuntimeException('node:// URI conversion requires a context node to be passed', 1409734235);
                }
                $contextNodeAddress = $this->nodeAddressFactory->createFromTraversableNode($contextNode)->getWorkspaceName();
                if ($contextNodeAddress === null) {
                    throw new \RuntimeException(sprintf('Failed to create node address for context node "%s"', $contextNode->getNodeAggregateIdentifier()));
                }
                $visibilityConstraints = $contextNodeAddress->isLive() ? VisibilityConstraints::frontend() : VisibilityConstraints::withoutRestrictions();
                $subgraph = $this->contentGraph->getSubgraphByIdentifier(
                    $contextNode->getContentStreamIdentifier(),
                    $contextNode->getDimensionSpacePoint(),
                    $visibilityConstraints
                );
                if ($subgraph === null) {
                    throw new \RuntimeException(sprintf('Failed to get SubContentGraph for context node "%s"', $contextNode->getNodeAggregateIdentifier()));
                }

                $node = $subgraph->findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier::fromString($uri->getHost()));
                if ($node === null) {
                    return null;
                }
                return new TraversableNode($node, $subgraph);
            case 'asset':
                /** @var AssetInterface|null $asset */
                /** @noinspection OneTimeUseVariablesInspection */
                $asset = $this->assetRepository->findByIdentifier($uri->getHost());
                return $asset;
            default:
                return null;
        }
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
