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
 * <typo3cr:widget.paginate parentNode="{parentNode}" as="paginatedNodes" configuration="{itemsPerPage: 5}">
 *   // use {paginatedNodes} inside a <f:for> loop.
 * </typo3cr:widget.paginate>
 * </code>
 *
 * <code title="specifying the nodes explicitly">
 * <typo3cr:widget.paginate nodes="{myNodes}" as="paginatedNodes" configuration="{itemsPerPage: 5}">
 *   // use {paginatedNodes} inside a <f:for> loop.
 * </typo3cr:widget.paginate>
 * </code>
 *
 * <code title="full configuration">
 * <typo3cr:widget.paginate parentNode="{parentNode}" as="paginatedNodes" nodeTypeFilter="Neos.Neos:Page" configuration="{itemsPerPage: 5, insertAbove: 1, insertBelow: 0, maximumNumberOfLinks: 10, maximumNumberOfNodes: 350}">
 *   // use {paginatedNodes} inside a <f:for> loop.
 * </typo3cr:widget.paginate>
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
     * Render this view helper
     *
     * @param string $as Variable name for the result set
     * @param \Neos\ContentRepository\Domain\Model\NodeInterface $parentNode The parent node of the child nodes to show (instead of specifying the specific node set)
     * @param array $nodes The specific collection of nodes to use for this paginator (instead of specifying the parentNode)
     * @param string $nodeTypeFilter A node type (or more complex filter) to filter for in the results
     * @param array $configuration Additional configuration
     * @return string
     */
    public function render($as, NodeInterface $parentNode = null, array $nodes = array(), $nodeTypeFilter = null, array $configuration = array('itemsPerPage' => 10, 'insertAbove' => false, 'insertBelow' => true, 'maximumNumberOfLinks' => 99, 'maximumNumberOfNodes' => 0))
    {
        $response = $this->initiateSubRequest();
        return $response->getContent();
    }
}
