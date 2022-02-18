<?php

namespace Neos\EventSourcedNeosAdjustments\ServiceControllers;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\SearchTerm;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Property\PropertyMapper;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Neos\Controller\BackendUserTranslationTrait;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Service\NodeSearchServiceInterface;
use Neos\Neos\View\Service\NodeJsonView;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Rudimentary REST service for nodes
 *
 * @Flow\Scope("singleton")
 */
class ServiceNodesController extends ActionController
{
    use BackendUserTranslationTrait;
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeSearchServiceInterface
     */
    protected $nodeSearchService;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;
    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => NodeJsonView::class
    ];

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array
     * @see http://www.iana.org/assignments/media-types/index.html
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json'
    ];

    /**
     * Shows a list of nodes
     *
     * @param string $searchTerm An optional search term used for filtering the list of nodes
     * @param array $nodeIdentifiers An optional list of node identifiers
     * @param string $workspaceName Name of the workspace to search in, "live" by default
     * @param array $dimensions Optional list of dimensions and their values which should be used for querying
     * @param array $nodeTypes A list of node types the list should be filtered by
     * @param NodeAddress $contextNode a node to use as context for the search
     * @return string
     */
    public function indexAction($searchTerm = '', array $nodeIdentifiers = [], $workspaceName = 'live', array $dimensions = [], array $nodeTypes = ['Neos.Neos:Document'], NodeAddress $contextNode = null)
    {
        $nodeAddress = $contextNode;
        unset($contextNode);
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions() // we are in a backend controller.
        );

        if ($nodeIdentifiers === []) {
            $entryNode = $nodeAccessor->findByIdentifier($nodeAddress->getNodeAggregateIdentifier());
            $nodes = $nodeAccessor->findDescendants(
                [$entryNode],
                $this->nodeTypeConstraintFactory->parseFilterString(implode(',', $nodeTypes)),
                SearchTerm::fulltext($searchTerm)
            );
            $this->view->assign('nodes', $nodes);
        } else {
            if (!empty($searchTerm)) {
                throw new \RuntimeException('Combination of $nodeIdentifiers and $searchTerm not supported');
            }

            $nodes = [];
            foreach ($nodeIdentifiers as $nodeAggregateIdentifier) {
                $node = $nodeAccessor->findByIdentifier(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier));
                if ($node !== null) {
                    $nodes[] = $node;
                }
            }
            $this->view->assign('nodes', $nodes);
        }
    }
}
