<?php
namespace TYPO3\TYPO3CR\Service;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * The node tree service
 * @Flow\Scope("singleton")
 */
class NodeTreeService
{

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;


    /**
     * @param NodeInterface $node
     * @param callable $action
     * @return void
     */
    public function traverseTree(NodeInterface $node, callable $action)
    {
        $continue = $action($node);
        if ($continue !== false && $node->hasChildNodes()) {
            foreach ($node->getChildNodes() as $childNode) {
                $this->traverseTree($childNode, $action);
            }
        }
        $this->nodeFactory->removeNodeFromCache($node);
        $node->getContext()->getFirstLevelNodeCache()
            ->unsetByIdentifier($node->getIdentifier())
            ->unsetByPath($node->getPath())
            ->unsetChildNodesByPath($node->getPath());
    }

}
