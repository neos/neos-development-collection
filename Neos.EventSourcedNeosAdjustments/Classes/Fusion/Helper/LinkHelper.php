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

use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Service\LinkingService;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Controller\ControllerContext;

/**
 * Eel helper for the linking service
 */
class LinkHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @param string|Uri $uri
     * @return boolean
     */
    public function hasSupportedScheme($uri)
    {
        return $this->linkingService->hasSupportedScheme($uri);
    }

    /**
     * @param string|Uri $uri
     * @return string
     */
    public function getScheme($uri)
    {
        return $this->linkingService->getScheme($uri);
    }

    /**
     * @param string|Uri $uri
     * @param TraversableNodeInterface $contextNode
     * @param ControllerContext $controllerContext
     * @return string
     */
    public function resolveNodeUri($uri, TraversableNodeInterface $contextNode, ControllerContext $controllerContext)
    {
        throw new \RuntimeException("TODO: implement");
    }

    /**
     * @param string|Uri $uri
     * @return string
     */
    public function resolveAssetUri($uri)
    {
        return $this->linkingService->resolveAssetUri($uri);
    }


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
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @param string|Uri $uri
     * @param TraversableNodeInterface $contextNode
     * @return TraversableNodeInterface|AssetInterface|NULL
     */
    public function convertUriToObject($uri, TraversableNodeInterface $contextNode = null)
    {
        if (empty($uri)) {
            return null;
        }

        $matches = null;
        if (!preg_match(LinkingService::PATTERN_SUPPORTED_URIS, $uri, $matches)) {
            return null;
        }

        switch ($matches[1]) {
            case 'node':
                $isLiveWorkspace = $this->nodeAddressFactory->createFromTraversableNode($contextNode)->getWorkspaceName()->isLive();
                $visibilityConstraints = $isLiveWorkspace ? VisibilityConstraints::frontend() : VisibilityConstraints::withoutRestrictions();

                $subgraph = $this->contentGraph->getSubgraphByIdentifier(
                    $contextNode->getContentStreamIdentifier(),
                    $contextNode->getDimensionSpacePoint(),
                    $visibilityConstraints
                );

                $node = $subgraph->findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier::fromString($matches[2]));
                if ($node) {
                    return new TraversableNode($node, $subgraph);
                } else {
                    return null;
                }


                break;
            case 'asset':
                return $this->assetRepository->findByIdentifier($matches[2]);
                break;
            default:
                return null;
        }
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
