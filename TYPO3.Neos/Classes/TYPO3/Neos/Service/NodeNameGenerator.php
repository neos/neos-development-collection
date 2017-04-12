<?php
namespace TYPO3\Neos\Service;

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
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A NodeNameGenerator to generate unique node names
 *
 * @Flow\Scope("singleton")
 */
class NodeNameGenerator
{
    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Service\NodeService
     */
    protected $nodeService;

    /**
     * Generate a node name, optionally based on a suggested "ideal" name
     *
     * @param NodeInterface $parentNode
     * @param string $idealNodeName Can be any string, doesn't need to be a valid node name.
     * @return string
     */
    public function generateUniqueNodeName(NodeInterface $parentNode, $idealNodeName = null)
    {
        $possibleNodeName = $this->generatePossibleNodeName($idealNodeName);
        $parentPath = rtrim($parentNode->getPath(), '/') . '/';

        while ($this->nodeService->nodePathExistsInAnyContext($parentPath . $possibleNodeName)) {
            $possibleNodeName = $this->generatePossibleNodeName();
        }

        return $possibleNodeName;
    }

    /**
     * @param string $idealNodeName
     * @return string
     */
    protected function generatePossibleNodeName($idealNodeName = null)
    {
        if ($idealNodeName !== null) {
            $possibleNodeName = \TYPO3\TYPO3CR\Utility::renderValidNodeName($idealNodeName);
        } else {
            $possibleNodeName = 'node-' . Algorithms::generateRandomString(13);
        }

        return $possibleNodeName;
    }
}
