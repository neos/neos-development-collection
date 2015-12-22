<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Render a bread crumb path by using the labels of documents leading to the given node path
 */
class DocumentBreadcrumbPathViewHelper extends AbstractViewHelper
{

    /**
     * @param NodeInterface $node A document node
     * @return string
     */
    public function render(NodeInterface $node)
    {
        $output = '/' . $node->getLabel();
        $flowQuery = new FlowQuery(array($node));
        $nodes = $flowQuery->parents('[instanceof TYPO3.Neos:Document]')->get();
        /** @var NodeInterface $node */
        foreach ($nodes as $node) {
            $output = '/' . $node->getLabel() . $output;
        }

        return $output;
    }
}
