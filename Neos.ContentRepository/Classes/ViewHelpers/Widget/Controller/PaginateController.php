<?php
namespace Neos\ContentRepository\ViewHelpers\Widget\Controller;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Arrays;
use Neos\FluidAdaptor\Core\Widget\AbstractWidgetController;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Exception\PageNotFoundException;

/**
 * The widget controller for the Node Paginate Widget
 */
class PaginateController extends AbstractWidgetController
{
    /**
     * @var NodeInterface
     */
    protected $parentNode;

    /**
     * @var array<\Neos\ContentRepository\Domain\Model\NodeInterface>
     */
    protected $nodes;

    /**
     * @var string
     */
    protected $nodeTypeFilter = '';

    /**
     * @var array
     */
    protected $configuration = ['itemsPerPage' => 10, 'insertAbove' => false, 'insertBelow' => true, 'maximumNumberOfLinks' => 99, 'maximumNumberOfItems' => 0, 'maximumNumberOfNodes' => 0];

    /**
     * @var integer
     */
    protected $currentPage = 1;

    /**
     * @var integer
     */
    protected $pagesBefore = 0;

    /**
     * @var integer
     */
    protected $pagesAfter = 0;

    /**
     * @var integer
     */
    protected $maximumNumberOfLinks = 99;

    /**
     * @var integer
     */
    protected $maximumNumberOfNodes = 0;

    /**
     * @var integer
     */
    protected $numberOfPages = 1;

    /**
     * @var integer
     */
    protected $displayRangeStart;

    /**
     * @var integer
     */
    protected $displayRangeEnd;

    /**
     * @return void
     */
    protected function initializeAction()
    {
        $this->parentNode = $this->widgetConfiguration['parentNode'];
        $this->nodes = $this->widgetConfiguration['nodes'];
        $this->nodeTypeFilter = $this->widgetConfiguration['nodeTypeFilter'] ?: null;
        $this->configuration = Arrays::arrayMergeRecursiveOverrule($this->configuration, $this->widgetConfiguration['configuration'], true);
        $this->maximumNumberOfNodes = $this->configuration['maximumNumberOfNodes'];

        $numberOfNodes = ($this->parentNode === null ? count($this->nodes) : $this->parentNode->getNumberOfChildNodes($this->nodeTypeFilter));
        if ($this->maximumNumberOfNodes > 0 && $numberOfNodes > $this->maximumNumberOfNodes) {
            $numberOfNodes = $this->maximumNumberOfNodes;
        }

        $this->numberOfPages = ceil($numberOfNodes / (integer)$this->configuration['itemsPerPage']);
        $this->maximumNumberOfLinks = (integer)$this->configuration['maximumNumberOfLinks'];
    }

    /**
     * @param integer $currentPage
     * @return void
     * @throws PageNotFoundException
     */
    public function indexAction($currentPage = 1)
    {
        $this->currentPage = (integer)$currentPage;
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        } elseif ($this->currentPage > $this->numberOfPages) {
            throw new PageNotFoundException();
        }

        $itemsPerPage = (integer)$this->configuration['itemsPerPage'];
        if ($this->maximumNumberOfNodes > 0 && $this->maximumNumberOfNodes < $itemsPerPage) {
            $itemsPerPage = $this->maximumNumberOfNodes;
        }
        $offset = ($this->currentPage > 1) ? (integer)($itemsPerPage * ($this->currentPage - 1)) : null;
        if ($this->parentNode === null) {
            $nodes = array_slice($this->nodes, $offset, $itemsPerPage);
        } else {
            $nodes = $this->parentNode->getChildNodes($this->nodeTypeFilter, $itemsPerPage, $offset);
        }

        $this->view->assign('contentArguments', [
            $this->widgetConfiguration['as'] => $nodes
        ]);
        $this->view->assign('configuration', $this->configuration);
        $this->view->assign('pagination', $this->buildPagination());
    }

    /**
     * If a certain number of links should be displayed, adjust before and after
     * amounts accordingly.
     *
     * @return void
     */
    protected function calculateDisplayRange()
    {
        $maximumNumberOfLinks = $this->maximumNumberOfLinks;
        if ($maximumNumberOfLinks > $this->numberOfPages) {
            $maximumNumberOfLinks = $this->numberOfPages;
        }
        $delta = floor($maximumNumberOfLinks / 2);
        $this->displayRangeStart = $this->currentPage - $delta;
        $this->displayRangeEnd = $this->currentPage + $delta + ($maximumNumberOfLinks % 2 === 0 ? 1 : 0);
        if ($this->displayRangeStart < 1) {
            $this->displayRangeEnd -= $this->displayRangeStart - 1;
        }
        if ($this->displayRangeEnd > $this->numberOfPages) {
            $this->displayRangeStart -= ($this->displayRangeEnd - $this->numberOfPages);
        }
        $this->displayRangeStart = (integer)max($this->displayRangeStart, 1);
        $this->displayRangeEnd = (integer)min($this->displayRangeEnd, $this->numberOfPages);
    }

    /**
     * Returns an array with the keys "pages", "current", "numberOfPages", "nextPage" & "previousPage"
     *
     * @return array
     */
    protected function buildPagination()
    {
        $this->calculateDisplayRange();
        $pages = [];
        for ($i = $this->displayRangeStart; $i <= $this->displayRangeEnd; $i++) {
            $pages[] = ['number' => $i, 'isCurrent' => ($i === $this->currentPage)];
        }
        $pagination = [
            'pages' => $pages,
            'current' => $this->currentPage,
            'numberOfPages' => $this->numberOfPages,
            'displayRangeStart' => $this->displayRangeStart,
            'displayRangeEnd' => $this->displayRangeEnd,
            'hasLessPages' => $this->displayRangeStart > 2,
            'hasMorePages' => $this->displayRangeEnd + 1 < $this->numberOfPages
        ];
        if ($this->currentPage < $this->numberOfPages) {
            $pagination['nextPage'] = $this->currentPage + 1;
        }
        if ($this->currentPage > 1) {
            $pagination['previousPage'] = $this->currentPage - 1;
        }
        return $pagination;
    }
}
