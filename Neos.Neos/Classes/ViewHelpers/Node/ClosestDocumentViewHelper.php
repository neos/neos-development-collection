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

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * ViewHelper to find the closest document node to a given node
 */
class ClosestDocumentViewHelper extends AbstractViewHelper
{
    /**
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('node', NodeInterface::class, 'Node', true);
    }

    /**
     * @return NodeInterface
     * @throws \Neos\Eel\Exception
     */
    public function render()
    {
        $flowQuery = new FlowQuery([$this->arguments['node']]);
        return $flowQuery->closest('[instanceof Neos.Neos:Document]')->get(0);
    }
}
