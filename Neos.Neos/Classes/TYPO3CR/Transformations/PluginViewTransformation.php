<?php
namespace Neos\Neos\TYPO3CR\Transformations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;

/**
 * Convert PluginViews references from node paths to identifiers
 */
class PluginViewTransformation extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var boolean
     */
    public $reverse = false;

    /**
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return true;
    }

    /**
     * Change the property on the given node.
     *
     * @param NodeData $node
     * @return NodeData
     */
    public function execute(NodeData $node)
    {
        $reference = (string)$node->getProperty('plugin');
        $workspace = $node->getWorkspace();
        do {
            if ($this->reverse === false && preg_match(NodeInterface::MATCH_PATTERN_PATH, $reference)) {
                $pluginNode = $this->nodeDataRepository->findOneByPath($reference, $node->getWorkspace());
            } else {
                $pluginNode = $this->nodeDataRepository->findOneByIdentifier($reference, $node->getWorkspace());
            }
            if (isset($pluginNode)) {
                break;
            }
            $workspace = $workspace->getBaseWorkspace();
        } while ($workspace && $workspace->getName() !== 'live');
        if (isset($pluginNode)) {
            $node->setProperty('plugin', $this->reverse === false ? $pluginNode->getIdentifier() : $pluginNode->getPath());
        }
        return $node;
    }
}
