<?php
namespace Neos\ContentRepository\ViewHelpers\Widget;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\ViewHelpers\Widget\Controller\PaginateController;

/**
 * This ViewHelper renders a Pagination of nodes.
 *
 * = Examples =
 *
 * <code title="specifying the parent node">
 * <cr:widget.paginate parentNode="{parentNode}" as="paginatedNodes" configuration="{itemsPerPage: 5}">
 *   // use {paginatedNodes} inside a <f:for> loop.
 * </cr:widget.paginate>
 * </code>
 *
 * <code title="specifying the nodes explicitly">
 * <cr:widget.paginate nodes="{myNodes}" as="paginatedNodes" configuration="{itemsPerPage: 5}">
 *   // use {paginatedNodes} inside a <f:for> loop.
 * </cr:widget.paginate>
 * </code>
 *
 * <code title="full configuration">
 * <cr:widget.paginate parentNode="{parentNode}" as="paginatedNodes" nodeTypeFilter="Neos.Neos:Page" configuration="{itemsPerPage: 5, insertAbove: 1, insertBelow: 0, maximumNumberOfLinks: 10, maximumNumberOfNodes: 350}">
 *   // use {paginatedNodes} inside a <f:for> loop.
 * </cr:widget.paginate>
 * </code>
 *
 * @api
 */
class PaginateViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @Flow\Inject
     * @var PaginateController
     */
    protected $controller;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('as', 'string', 'Variable name for the result set', true);
        $this->registerArgument('parentNode', NodeInterface::class, 'The parent node of the child nodes to show (instead of specifying the specific node set)');
        $this->registerArgument('nodes', 'array', 'The specific collection of nodes to use for this paginator (instead of specifying the parentNode)', false, []);
        $this->registerArgument('nodeTypeFilter', 'string', 'A node type (or more complex filter) to filter for in the results');
        $this->registerArgument('configuration', 'array', 'Widget configuration', false, ['itemsPerPage' => 10, 'insertAbove' => false, 'insertBelow' => true, 'maximumNumberOfLinks' => 99, 'maximumNumberOfNodes' => 0]);
    }

    /**
     * Render this view helper
     *
     * @return string
     * @throws \Neos\Flow\Mvc\Exception\InfiniteLoopException
     * @throws \Neos\FluidAdaptor\Core\Widget\Exception\InvalidControllerException
     * @throws \Neos\FluidAdaptor\Core\Widget\Exception\MissingControllerException
     */
    public function render(): string
    {
        return $this->initiateSubRequest();
    }
}
