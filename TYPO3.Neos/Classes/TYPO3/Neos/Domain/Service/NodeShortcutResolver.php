<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Neos\Service\LinkingService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Flow\Annotations as Flow;

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
     * Resolves a shortcut node to the target. The return value can be
     *
     * * a NodeInterface instance if the target is a node or a node:// URI
     * * a string (in case the target is a plain text URI or an asset:// URI)
     * * NULL in case the shortcut cannot be resolved
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
     * @return NodeInterface|string|NULL
     */
    public function resolveShortcutTarget(NodeInterface $node)
    {
        $infiniteLoopPrevention = 0;
        while ($node->getNodeType()->isOfType('TYPO3.Neos:Shortcut') && $infiniteLoopPrevention < 50) {
            $infiniteLoopPrevention++;
            switch ($node->getProperty('targetMode')) {
                case 'selectedTarget':
                    $target = $node->getProperty('target');
                    if ($this->linkingService->hasSupportedScheme($target)) {
                        $targetObject = $this->linkingService->convertUriToObject($target, $node);
                        if ($targetObject instanceof NodeInterface) {
                            $node = $targetObject;
                        } elseif ($targetObject instanceof AssetInterface) {
                            return $this->linkingService->resolveAssetUri($target);
                        }
                    } else {
                        return $target;
                    }
                    break;
                case 'parentNode':
                    $node = $node->getParent();
                    break;
                case 'firstChildNode':
                default:
                    $childNodes = $node->getChildNodes('TYPO3.Neos:Document');
                    if ($childNodes !== array()) {
                        $node = reset($childNodes);
                    } else {
                        return null;
                    }
            }
        }

        return $node;
    }
}
