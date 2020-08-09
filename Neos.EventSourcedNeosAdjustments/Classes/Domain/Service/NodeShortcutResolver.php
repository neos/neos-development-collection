<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Domain\Service;

/*
 * This file is part of the Neos.EventSourcedNeosAdjustments package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Neos\Controller\Exception\NodeNotFoundException;
use Neos\Neos\Service\LinkingService;
use Neos\Flow\Annotations as Flow;

/**
 * Can resolve the target for a given shortcut.
 *
 * @Flow\Scope("singleton")
 */
class NodeShortcutResolver
{
    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * Resolves a shortcut node to the target. The return value can be
     *
     * * string (in case the target is a plain text URI, a node:// URI, an asset:// URI or a node)
     * * NULL in case the shortcut cannot be resolved
     *
     * @param ContentSubgraphInterface $subgraph
     * @param NodeInterface $shortcut
     * @param NodeAddress $nodeAddress
     * @param UriBuilder $uriBuilder
     * @param string $format
     * @return string|null
     * @throws NodeNotFoundException
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function resolveShortcutTarget(ContentSubgraphInterface $subgraph, NodeInterface $shortcut, NodeAddress $nodeAddress, UriBuilder $uriBuilder, string $format): ?string
    {
        $infiniteLoopPrevention = 0;
        $resolvedNode = $shortcut;
        while ($resolvedNode && $resolvedNode->getNodeType()->isOfType('Neos.Neos:Shortcut') && $infiniteLoopPrevention < 50) {
            $infiniteLoopPrevention++;
            switch ($resolvedNode->getProperty('targetMode')) {
                case 'selectedTarget':
                    $target = $resolvedNode->getProperty('target');
                    if ($this->linkingService->hasSupportedScheme($target)) {
                        $targetObject = $this->linkingService->convertUriToObject($target, $resolvedNode);
                        if ($targetObject instanceof NodeInterface) {
                            $resolvedNode = $targetObject;
                        } elseif ($targetObject instanceof AssetInterface) {
                            return $this->linkingService->resolveAssetUri($target);
                        }
                    } else {
                        return $target;
                    }
                    break;
                case 'parentNode':
                    $resolvedNode = $subgraph->findParentNode($resolvedNode->getNodeAggregateIdentifier());
                    break;
                case 'firstChildNode':
                default:
                    $childNodes = $subgraph->findChildNodes($resolvedNode->getNodeAggregateIdentifier(), $this->nodeTypeConstraintFactory->parseFilterString('Neos.Neos:Document'), 1);
                    $resolvedNode = reset($childNodes) ?? null;
            }
        }

        if ($resolvedNode === $shortcut) {
            throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1502793585);
        }

        $newNodeAddress = $nodeAddress->withNodeAggregateIdentifier($resolvedNode->getNodeAggregateIdentifier());
        $uriBuilder->reset();
        $uriBuilder->setFormat($format);

        return $uriBuilder->setCreateAbsoluteUri(true)
            ->uriFor('show', ['node' => $newNodeAddress], 'Frontend\Node', 'Neos.Neos');
    }
}
