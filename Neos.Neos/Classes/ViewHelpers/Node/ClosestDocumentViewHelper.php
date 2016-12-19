<?php
namespace Neos\Neos\ViewHelpers\Node;

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
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * ViewHelper to find the closest document node to a given node
 */
class ClosestDocumentViewHelper extends AbstractViewHelper
{
    /**
     * @param NodeInterface $node
     * @return NodeInterface
     */
    public function render(NodeInterface $node)
    {
        $flowQuery = new FlowQuery(array($node));
        return $flowQuery->closest('[instanceof Neos.Neos:Document]')->get(0);
    }
}
