<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Controller\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\ContentRepository\Core\NodeType\NodeTypeConstraintParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\CatchUpTriggerWithSynchronousOption;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Property\PropertyMapper;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Neos\Controller\BackendUserTranslationTrait;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Ui\Domain\Service\NodePropertyConverterService;
use Neos\Neos\View\Service\NodeJsonView;

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
     * @var NodePropertyConverterService
     */
    protected $nodePropertyConverterService;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject()
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

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
     * @param array $nodeIds An optional list of node identifiers
     * @phpstan-param array<string>|string $nodeIds
     * @param string $workspaceName Name of the workspace to search in, "live" by default
     * @param array $dimensions Optional list of dimensions and their values which should be used for querying
     * @phpstan-param array<mixed> $dimensions
     * @param array $nodeTypes A list of node types the list should be filtered by (array(string)
     * @phpstan-param array<string> $nodeTypes
     * @param string $contextNode a node to use as context for the search
     * @param array $nodeIdentifiers An optional list of legacy node identifiers, @deprecated
     * @phpstan-param array<string>|string $nodeIdentifiers
     */
    public function indexAction(
        string $searchTerm = '',
        array|string $nodeIds = [],
        string $workspaceName = 'live',
        array $dimensions = [],
        array $nodeTypes = ['Neos.Neos:Document'],
        string $contextNode = null,
        array|string $nodeIdentifiers = []
    ): void {
        $nodeIds = $nodeIds ?: $nodeIdentifiers;
        $nodeIds = is_array($nodeIds) ? $nodeIds : [$nodeIds];
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $nodePath = $contextNode
            ? AbsoluteNodePath::tryFromString($contextNode)
            : null;
        $nodeAddress = null;
        if (!$nodePath) {
            $nodeAddress = $contextNode
                ? NodeAddressFactory::create($contentRepository)->createFromUriString($contextNode)
                : null;
        }

        unset($contextNode);
        if (is_null($nodeAddress)) {
            $workspace = $contentRepository->getWorkspaceFinder()->findOneByName(
                WorkspaceName::fromString($workspaceName)
            );
            if (is_null($workspace)) {
                throw new \InvalidArgumentException(
                    'Could not resolve a node address for the given parameters.',
                    1645631728
                );
            }
            $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                $workspace->currentContentStreamId,
                DimensionSpacePoint::fromLegacyDimensionArray($dimensions),
                VisibilityConstraints::withoutRestrictions() // we are in a backend controller.
            );
        } else {
            $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                $nodeAddress->contentStreamId,
                $nodeAddress->dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions() // we are in a backend controller.
            );
        }

        if ($nodeIds === [] && (!is_null($nodeAddress) || !is_null($nodePath))) {
            if (!is_null($nodeAddress)) {
                $entryNode = $subgraph->findNodeById($nodeAddress->nodeAggregateId);
            } else {
                /** @var AbsoluteNodePath $nodePath */
                $entryNode = $subgraph->findNodeByAbsolutePath($nodePath);
            }

            $nodes = !is_null($entryNode) ? $subgraph->findDescendantNodes(
                $entryNode->nodeAggregateId,
                FindDescendantNodesFilter::create(
                    nodeTypeConstraints: NodeTypeConstraints::create(
                        NodeTypeNames::fromStringArray($nodeTypes),
                        NodeTypeNames::createEmpty()
                    ),
                    searchTerm: $searchTerm,
                )
            ) : [];
        } else {
            if (!empty($searchTerm)) {
                throw new \RuntimeException('Combination of $nodeIdentifiers and $searchTerm not supported');
            }

            $nodes = [];
            foreach ($nodeIds as $nodeAggregateId) {
                $node = $subgraph->findNodeById(
                    NodeAggregateId::fromString($nodeAggregateId)
                );
                if ($node !== null) {
                    $nodes[] = $node;
                }
            }
        }
        $this->view->assign('nodes', $nodes);
    }


    /**
     * Shows a specific node
     *
     * @param string $identifier Specifies the node to look up (NodeAggregateId)
     * @param string $workspaceName Name of the workspace to use for querying the node
     * @param array $dimensions Optional list of dimensions and their values which should be
     * used for querying the specified node
     * @phpstan-param array<string,array<string>> $dimensions
     */
    public function showAction(string $identifier, string $workspaceName = 'live', array $dimensions = []): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($identifier);
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspace = $contentRepository->getWorkspaceFinder()
            ->findOneByName(WorkspaceName::fromString($workspaceName));
        assert($workspace instanceof Workspace);

        $dimensionSpacePoint = DimensionSpacePoint::fromLegacyDimensionArray($dimensions);
        $subgraph = $contentRepository->getContentGraph()
            ->getSubgraph(
                $workspace->currentContentStreamId,
                $dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );

        $node = $subgraph->findNodeById($nodeAggregateId);

        if ($node === null) {
            $this->addExistingNodeVariantInformationToResponse(
                $nodeAggregateId,
                $workspace->currentContentStreamId,
                $dimensionSpacePoint,
                $contentRepository
            );
            $this->throwStatus(404);
        }

        $convertedNodeProperties = $this->nodePropertyConverterService->getPropertiesArray($node);
        array_walk($convertedNodeProperties, function (&$value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
        });

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($node)->serializeForUri();

        $this->view->assignMultiple([
            'node' => $node,
            'nodeContextPath' => $nodeAddress,
            'convertedNodeProperties' => $convertedNodeProperties
        ]);
    }

    /**
     * Create a new node from an existing one
     *
     * The "mode" property defines the basic mode of operation. Currently supported modes:
     *
     * 'adoptFromAnotherDimension': Adopts the single node from another dimension
     *   - $identifier, $workspaceName and $sourceDimensions specify the source node
     *   - $identifier, $workspaceName and $dimensions specify the target node
     *
     * @param string $mode
     * @param string $identifier Specifies the identifier of the node to be created; if source
     * @param string $workspaceName Name of the workspace where to create the node in
     * @param array $dimensions Optional list of dimensions and their values in which the node should be created
     * @phpstan-param array<string,array<string>> $dimensions
     * @param array $sourceDimensions
     * @phpstan-param array<string,array<string>> $sourceDimensions
     */
    public function createAction(
        string $mode,
        string $identifier,
        string $workspaceName = 'live',
        array $dimensions = [],
        array $sourceDimensions = []
    ): void {
        $nodeAggregateId = NodeAggregateId::fromString($identifier);
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspace = $contentRepository->getWorkspaceFinder()
            ->findOneByName(WorkspaceName::fromString($workspaceName));
        assert($workspace instanceof Workspace);

        $sourceSubgraph = $contentRepository->getContentGraph()
            ->getSubgraph(
                $workspace->currentContentStreamId,
                DimensionSpacePoint::fromLegacyDimensionArray($sourceDimensions),
                VisibilityConstraints::withoutRestrictions()
            );

        $targetDimensionSpacePoint = DimensionSpacePoint::fromLegacyDimensionArray($dimensions);
        $targetSubgraph = $contentRepository->getContentGraph()
            ->getSubgraph(
                $workspace->currentContentStreamId,
                $targetDimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );

        if ($mode === 'adoptFromAnotherDimension' || $mode === 'adoptFromAnotherDimensionAndCopyContent') {
            CatchUpTriggerWithSynchronousOption::synchronously(fn() =>
                $this->adoptNodeAndParents(
                    $workspace->currentContentStreamId,
                    $nodeAggregateId,
                    $sourceSubgraph,
                    $targetSubgraph,
                    $targetDimensionSpacePoint,
                    $contentRepository,
                    $mode === 'adoptFromAnotherDimensionAndCopyContent'
                ));

            $this->redirect('show', null, null, [
                'identifier' => $nodeAggregateId->value,
                'workspaceName' => $workspaceName,
                'dimensions' => $dimensions
            ]);
        } else {
            $this->throwStatus(400, sprintf('The create mode "%s" is not supported.', $mode));
        }
    }

    /**
     * If the node is not found, we *first* want to figure out whether the node exists in other dimensions
     * or is really non-existent
     */
    protected function addExistingNodeVariantInformationToResponse(
        NodeAggregateId $identifier,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        ContentRepository $contentRepository
    ): void {
        $contentGraph = $contentRepository->getContentGraph();
        $nodeTypeManager = $contentRepository->getNodeTypeManager();
        $nodeAggregate = $contentGraph->findNodeAggregateById($contentStreamId, $identifier);

        if ($nodeAggregate && $nodeAggregate->coveredDimensionSpacePoints->count() > 0) {
            $this->response->setHttpHeader('X-Neos-Node-Exists-In-Other-Dimensions', 'true');

            // If the node exists in another dimension, we want to know how many nodes in the rootline are also
            // missing for the target dimension. This is needed in the UI to tell the user if nodes will be
            // materialized recursively upwards in the rootline. To find the node path for the given identifier,
            // we just use the first result. This is a safe assumption at least for "Document" nodes (aggregate=true),
            // because they are always moved in-sync.
            if ($nodeTypeManager->getNodeType($nodeAggregate->nodeTypeName)->isAggregate()) {
                // TODO: we would need the SourceDimensions parameter (as in Create()) to ensure the correct
                // rootline is traversed. Here, we, as a workaround, simply use the 1st aggregate for now.

                $missingNodesOnRootline = 0;
                while (
                    $parentAggregate = self::firstNodeAggregate(
                        $contentGraph->findParentNodeAggregates($contentStreamId, $identifier)
                    )
                ) {
                    if (!$parentAggregate->coversDimensionSpacePoint($dimensionSpacePoint)) {
                        $missingNodesOnRootline++;
                    }

                    $identifier = $parentAggregate->nodeAggregateId;
                }

                // TODO: possibly off-by-one-or-two errors :D
                if ($missingNodesOnRootline > 0) {
                    $this->response->setHttpHeader(
                        'X-Neos-Nodes-Missing-On-Rootline',
                        (string)$missingNodesOnRootline
                    );
                }
            }
        }
    }

    /**
     * @param iterable<NodeAggregate> $nodeAggregates
     * @return NodeAggregate|null
     */
    private static function firstNodeAggregate(iterable $nodeAggregates): ?NodeAggregate
    {
        foreach ($nodeAggregates as $nodeAggregate) {
            return $nodeAggregate;
        }
        return null;
    }

    /**
     * Adopt (translate) the given node and parents that are not yet visible to the given context
     *
     * @param NodeAggregateId $nodeAggregateId
     * @param ContentSubgraphInterface $sourceSubgraph
     * @param ContentSubgraphInterface $targetSubgraph
     * @param ContentRepository $contentRepository
     * @param boolean $copyContent true if the content from the nodes that are translated should be copied
     * @return void
     */
    protected function adoptNodeAndParents(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        ContentSubgraphInterface $sourceSubgraph,
        ContentSubgraphInterface $targetSubgraph,
        DimensionSpacePoint $targetDimensionSpacePoint,
        ContentRepository $contentRepository,
        bool $copyContent
    ) {
        $identifiersFromRootlineToTranslate = [];
        while (
            $nodeAggregateId
            && $targetSubgraph->findNodeById($nodeAggregateId) === null
        ) {
            $identifiersFromRootlineToTranslate[] = $nodeAggregateId;
            $nodeAggregateId = $sourceSubgraph->findParentNode($nodeAggregateId)
                ?->nodeAggregateId;
        }
        // $identifiersFromRootlineToTranslate is now bottom-to-top; so we need to reverse
        // them to know what we need to create.
        // TODO: TEST THAT AUTO CREATED CHILD NODES WORK (though this should not have influence)

        foreach (array_reverse($identifiersFromRootlineToTranslate) as $identifier) {
            assert($identifier instanceof NodeAggregateId);
            // NOTE: for creating node variants, we need to find the ORIGIN DSP
            // of the source node (in order to unambiguously identify it);
            // so we need to load it from the source subgraph
            $sourceNode = $sourceSubgraph->findNodeById($identifier);
            if (!$sourceNode) {
                throw new \RuntimeException('Source node for Node Aggregate ID ' . $identifier->value
                    . ' not found. This should never happen.', 1660905374);
            }
            $contentRepository->handle(
                new CreateNodeVariant(
                    $contentStreamId,
                    $identifier,
                    $sourceNode->originDimensionSpacePoint,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($targetDimensionSpacePoint),
                )
            )->block();

            if ($copyContent === true) {
                $contentNodeConstraint = NodeTypeConstraints::fromFilterString('!Neos.Neos:Document');
                $this->createNodeVariantsForChildNodes(
                    $contentStreamId,
                    $identifier,
                    $contentNodeConstraint,
                    $sourceSubgraph,
                    $targetSubgraph,
                    $targetDimensionSpacePoint,
                    $contentRepository
                );
            }
        }
    }

    private function createNodeVariantsForChildNodes(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeId,
        NodeTypeConstraints $constraints,
        ContentSubgraphInterface $sourceSubgraph,
        ContentSubgraphInterface $targetSubgraph,
        DimensionSpacePoint $targetDimensionSpacePoint,
        ContentRepository $contentRepository
    ): void {
        foreach (
            $sourceSubgraph->findChildNodes(
                $parentNodeId,
                FindChildNodesFilter::create(nodeTypeConstraints: $constraints)
            ) as $childNode
        ) {
            if ($childNode->classification->isRegular()) {
                // Tethered nodes' variants are automatically created when the parent is translated.
                // TODO: DOES THIS MAKE SENSE?
                $contentRepository->handle(
                    new CreateNodeVariant(
                        $contentStreamId,
                        $childNode->nodeAggregateId,
                        $childNode->originDimensionSpacePoint,
                        OriginDimensionSpacePoint::fromDimensionSpacePoint($targetDimensionSpacePoint),
                    )
                )->block();
            }

            $this->createNodeVariantsForChildNodes(
                $contentStreamId,
                $childNode->nodeAggregateId,
                $constraints,
                $sourceSubgraph,
                $targetSubgraph,
                $targetDimensionSpacePoint,
                $contentRepository
            );
        }
    }
}
