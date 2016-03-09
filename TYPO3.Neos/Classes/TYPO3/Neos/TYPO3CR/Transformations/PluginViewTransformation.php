<?php
namespace TYPO3\Neos\TYPO3CR\Transformations;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Migration\Transformations\AbstractTransformation;

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
