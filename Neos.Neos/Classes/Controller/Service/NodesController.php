<?php
namespace Neos\Neos\Controller\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\SearchTerm;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Property\PropertyMapper;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Neos\Controller\BackendUserTranslationTrait;
use Neos\Neos\Domain\Service\NodeSearchServiceInterface;
use Neos\Neos\View\Service\NodeJsonView;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;

/**
 * Rudimentary REST service for nodes
 *
 * @Flow\Scope("singleton")
 */
class NodesController extends ActionController
{
    use BackendUserTranslationTrait;

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
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @var array<string,string>
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => NodeJsonView::class
    ];

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array<int,string>
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
     * @param array $dimensions Optional list of dimensions
     *                                        and their values which should be used for querying
     * @param array $nodeTypes A list of node types the list should be filtered by (array(string)
     * @param string $contextNode a node to use as context for the search
     */
    /* @phpstan-ignore-next-line */
    public function indexAction(
        string $searchTerm = '',
        array $nodeIdentifiers = [],
        string $workspaceName = 'live',
        array $dimensions = [],
        array $nodeTypes = ['Neos.Neos:Document'],
        string $contextNode = null
    ): void {
        $nodeAddress = $contextNode ? $this->nodeAddressFactory->createFromUriString($contextNode) : null;
        unset($contextNode);
        if (is_null($nodeAddress)) {
            $workspace = $this->workspaceFinder->findOneByName(WorkspaceName::fromString($workspaceName));
            if (is_null($workspace)) {
                throw new \InvalidArgumentException(
                    'Could not resolve a node address for the given parameters.',
                    1645631728
                );
            }
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $workspace->getCurrentContentStreamIdentifier(),
                DimensionSpacePoint::fromLegacyDimensionArray($dimensions),
                VisibilityConstraints::withoutRestrictions() // we are in a backend controller.
            );
        } else {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $nodeAddress->contentStreamIdentifier,
                $nodeAddress->dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions() // we are in a backend controller.
            );
        }

        if ($nodeIdentifiers === [] && !is_null($nodeAddress)) {
            $entryNode = $nodeAccessor->findByIdentifier($nodeAddress->nodeAggregateIdentifier);
            $nodes = !is_null($entryNode) ? $nodeAccessor->findDescendants(
                [$entryNode],
                $this->nodeTypeConstraintFactory->parseFilterString(implode(',', $nodeTypes)),
                SearchTerm::fulltext($searchTerm)
            ) : [];
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
        }
        $this->view->assign('nodes', $nodes);
    }
}
