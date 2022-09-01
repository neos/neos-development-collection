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

namespace Neos\Neos\FrontendRouting;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\Neos\FrontendRouting\Exception\InvalidShortcutException;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;
use Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathFinder;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathProjection;
use Psr\Http\Message\UriInterface;

/**
 * Can resolve the target for a given shortcut.
 * Used for Neos Routing ({@see EventSourcedFrontendNodeRoutePartHandler}),
 * and redirects to a shortcut target when visiting the shortcut itself.
 *
 * @Flow\Scope("singleton")
 */
class NodeShortcutResolver
{
    private AssetRepository $assetRepository;

    private ResourceManager $resourceManager;

    public function __construct(
        AssetRepository $assetRepository,
        ResourceManager $resourceManager
    ) {
        $this->assetRepository = $assetRepository;
        $this->resourceManager = $resourceManager;
    }

    /**
     * "adapter" for {@see resolveNode} when working with NodeAddresses.
     * Note: The ContentStreamId is not required for this service,
     * because it is only covering the live workspace
     *
     * @param NodeAddress $nodeAddress
     * @return NodeAddress|UriInterface NodeAddress is returned if we want to link to another node
     * (i.e. node is NOT a shortcut node; or target is a node);
     * or UriInterface for links to fixed URLs (Asset URLs or external URLs)
     * @throws \Neos\Neos\FrontendRouting\Exception\InvalidShortcutException
     * @throws NodeNotFoundException
     */
    public function resolveShortcutTarget(NodeAddress $nodeAddress, ContentRepository $contentRepository)
    {
        $documentUriPathFinder = $contentRepository->projectionState(DocumentUriPathFinder::class);
        $documentNodeInfo = $documentUriPathFinder->getByIdAndDimensionSpacePointHash(
            $nodeAddress->nodeAggregateId,
            $nodeAddress->dimensionSpacePoint->hash
        );
        $resolvedTarget = $this->resolveNode($documentNodeInfo, $contentRepository);
        if ($resolvedTarget instanceof UriInterface) {
            return $resolvedTarget;
        }
        if ($resolvedTarget === $documentNodeInfo) {
            return $nodeAddress;
        }
        return $nodeAddress->withNodeAggregateId($documentNodeInfo->getNodeAggregateId());
    }

    /**
     * This method is used during routing (when creating URLs), to directly generate URLs to the shortcut TARGET,
     * if linking to a shortcut.
     * Note: The ContentStreamId is not required for this service,
     * because it is only covering the live workspace
     *
     * @param DocumentNodeInfo $documentNodeInfo
     * @return \Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo|UriInterface
     * DocumentNodeInfo is returned if we want to link to another node
     * (i.e. node is NOT a shortcut node; or target is a node);
     * or UriInterface for links to fixed URLs (Asset URLs or external URLs)
     * @throws \Neos\Neos\FrontendRouting\Exception\InvalidShortcutException
     */
    public function resolveNode(
        DocumentNodeInfo $documentNodeInfo,
        ContentRepository $contentRepository
    ): DocumentNodeInfo|UriInterface {
        $documentUriPathFinder = $contentRepository->projectionState(DocumentUriPathFinder::class);
        $shortcutRecursionLevel = 0;
        while ($documentNodeInfo->isShortcut()) {
            if (++$shortcutRecursionLevel > 50) {
                throw new InvalidShortcutException(sprintf(
                    'Shortcut recursion level reached after %d levels',
                    $shortcutRecursionLevel
                ), 1599035282);
            }
            switch ($documentNodeInfo->getShortcutMode()) {
                case 'parentNode':
                    try {
                        $documentNodeInfo = $documentUriPathFinder->getParentNode($documentNodeInfo);
                    } catch (NodeNotFoundException $e) {
                        throw new InvalidShortcutException(sprintf(
                            'Shortcut Node "%s" points to a non-existing parent node "%s"',
                            $documentNodeInfo,
                            $documentNodeInfo->getNodeAggregateId()
                        ), 1599669406, $e);
                    }
                    if ($documentNodeInfo->isDisabled()) {
                        throw new InvalidShortcutException(sprintf(
                            'Shortcut Node "%s" points to disabled parent node "%s"',
                            $documentNodeInfo,
                            $documentNodeInfo->getNodeAggregateId()
                        ), 1599664517);
                    }
                    continue 2;
                case 'firstChildNode':
                    try {
                        $documentNodeInfo = $documentUriPathFinder->getFirstEnabledChildNode(
                            $documentNodeInfo->getNodeAggregateId(),
                            $documentNodeInfo->getDimensionSpacePointHash()
                        );
                    } catch (\Exception $e) {
                        throw new InvalidShortcutException(sprintf(
                            'Failed to fetch firstChildNode in Node "%s": %s',
                            $documentNodeInfo,
                            $e->getMessage()
                        ), 1599043861, $e);
                    }
                    continue 2;
                case 'selectedTarget':
                    try {
                        $targetUri = $documentNodeInfo->getShortcutTargetUri();
                    } catch (\Exception $e) {
                        throw new InvalidShortcutException(sprintf(
                            'Invalid shortcut target in Node "%s": %s',
                            $documentNodeInfo,
                            $e->getMessage()
                        ), 1599043489, $e);
                    }
                    if ($targetUri->getScheme() === 'node') {
                        $targetNodeAggregateId = NodeAggregateId::fromString($targetUri->getHost());
                        try {
                            $documentNodeInfo = $documentUriPathFinder->getByIdAndDimensionSpacePointHash(
                                $targetNodeAggregateId,
                                $documentNodeInfo->getDimensionSpacePointHash()
                            );
                        } catch (\Exception $e) {
                            throw new InvalidShortcutException(sprintf(
                                'Failed to load selectedTarget node in Node "%s": %s',
                                $documentNodeInfo,
                                $e->getMessage()
                            ), 1599043803, $e);
                        }
                        if ($documentNodeInfo->isDisabled()) {
                            throw new InvalidShortcutException(sprintf(
                                'Shortcut target in Node "%s" points to disabled node "%s"',
                                $documentNodeInfo,
                                $documentNodeInfo->getNodeAggregateId()
                            ), 1599664423);
                        }
                        continue 2;
                    }
                    if ($targetUri->getScheme() === 'asset') {
                        $asset = $this->assetRepository->findByIdentifier($targetUri->getHost());
                        if (!$asset instanceof AssetInterface) {
                            throw new InvalidShortcutException(sprintf(
                                'Failed to load selectedTarget asset in Node "%s", probably it was deleted',
                                $documentNodeInfo
                            ), 1599314109);
                        }
                        $assetUri = $this->resourceManager->getPublicPersistentResourceUri($asset->getResource());
                        if (!is_string($assetUri)) {
                            throw new InvalidShortcutException(sprintf(
                                'Failed to resolve asset URI in Node "%s", probably it was deleted',
                                $documentNodeInfo
                            ), 1599314203);
                        }
                        return new Uri($assetUri);
                    }
                    return $targetUri;
                default:
                    throw new InvalidShortcutException(sprintf(
                        'Unsupported shortcut mode "%s" in Node "%s"',
                        $documentNodeInfo->getShortcutMode(),
                        $documentNodeInfo
                    ), 1598194032);
            }
        }
        return $documentNodeInfo;
    }
}
