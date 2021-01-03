<?php

namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Neos\Service\LinkingService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;

/**
 * Can resolve the target for a given node.
 *
 * @Flow\Scope("singleton")
 */
class NodeShortcutResolver
{
    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Resolves a shortcut node to the target. The return value can be
     *
     * * a NodeInterface instance if the target is a node or a node:// URI
     * * a string (in case the target is a plain text URI or an asset:// URI)
     * * NULL in case the shortcut cannot be resolved
     *
     * @param NodeInterface $node
     * @return NodeInterface|string|NULL
     */
    public function resolveShortcutTarget(NodeInterface $node)
    {
        $infiniteLoopPrevention = 0;
        while ($node->getNodeType()->isOfType('Neos.Neos:Shortcut') && $infiniteLoopPrevention < 50) {
            $infiniteLoopPrevention++;
            switch ($node->getProperty('targetMode')) {
                case 'selectedTarget':
                    $target = $node->getProperty('target');
                    if (!$this->linkingService->hasSupportedScheme($target)) {
                        return $target;
                    }

                    $targetObject = $this->linkingService->convertUriToObject($target, $node);
                    if ($targetObject instanceof NodeInterface) {
                        $node = $targetObject;
                    } elseif ($targetObject instanceof AssetInterface) {
                        return $this->linkingService->resolveAssetUri($target);
                    } else {
                        $this->logger->warning(sprintf('Could not resolve shortcut target for node "%s". The selected target "%s" could not be resolved.', $node->getPath(), $target), LogEnvironment::fromMethodName(__METHOD__));
                    }
                    break;

                case 'parentNode':
                    $node = $node->getParent();
                    break;

                case 'firstChildNode':
                default:
                    $childNodes = $node->getChildNodes('Neos.Neos:Document');
                    if ($childNodes !== []) {
                        $node = reset($childNodes);
                    } else {
                        $this->logger->warning(sprintf('Could not resolve shortcut target for node "%s". The node has no child nodes to link to.', $node->getPath()), LogEnvironment::fromMethodName(__METHOD__));
                        return null;
                    }
            }
        }

        return $node;
    }
}
