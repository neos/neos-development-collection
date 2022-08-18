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

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintParser;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
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
        $contentRepositoryIdentifier = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryIdentifier;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);


        $nodeAddress = $contextNode
            ? NodeAddressFactory::create($contentRepository)->createFromUriString($contextNode)
            : null;

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
                $workspace->getCurrentContentStreamIdentifier(),
                DimensionSpacePoint::fromLegacyDimensionArray($dimensions),
                VisibilityConstraints::withoutRestrictions() // we are in a backend controller.
            );
        } else {
            $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                $nodeAddress->contentStreamIdentifier,
                $nodeAddress->dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions() // we are in a backend controller.
            );
        }

        if ($nodeIdentifiers === [] && !is_null($nodeAddress)) {
            $entryNode = $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->nodeAggregateIdentifier);
            $nodes = !is_null($entryNode) ? $subgraph->findDescendants(
                [$entryNode->nodeAggregateIdentifier],
                NodeTypeConstraintParser::create($contentRepository->getNodeTypeManager())->parseFilterString(
                    implode(',', $nodeTypes)
                ),
                SearchTerm::fulltext($searchTerm)
            ) : [];
        } else {
            if (!empty($searchTerm)) {
                throw new \RuntimeException('Combination of $nodeIdentifiers and $searchTerm not supported');
            }

            $nodes = [];
            foreach ($nodeIdentifiers as $nodeAggregateIdentifier) {
                $node = $subgraph->findNodeByNodeAggregateIdentifier(
                    NodeAggregateIdentifier::fromString($nodeAggregateIdentifier)
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
     * @param string $identifier Specifies the node to look up (NodeAggregateIdentifier)
     * @param string $workspaceName Name of the workspace to use for querying the node
     * @param array $dimensions Optional list of dimensions and their values which should be
     * used for querying the specified node
     * @phpstan-param array<string,array<string>> $dimensions
     */
    public function showAction(string $identifier, string $workspaceName = 'live', array $dimensions = []): void
    {
        $identifier = NodeAggregateIdentifier::fromString($identifier);
        $contentRepositoryIdentifier = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryIdentifier;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);

        $workspace = $contentRepository->getWorkspaceFinder()
            ->findOneByName(WorkspaceName::fromString($workspaceName));
        assert($workspace instanceof Workspace);

        $dimensionSpacePoint = DimensionSpacePoint::fromLegacyDimensionArray($dimensions);
        $subgraph = $contentRepository->getContentGraph()
            ->getSubgraph(
                $workspace->getCurrentContentStreamIdentifier(),
                $dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );

        $node = $subgraph->findNodeByNodeAggregateIdentifier($identifier);

        if ($node === null) {
            $this->addExistingNodeVariantInformationToResponse(
                $identifier,
                $workspace->getCurrentContentStreamIdentifier(),
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

        $this->view->assignMultiple([
            'node' => $node,
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
        $identifier = NodeAggregateIdentifier::fromString($identifier);
        $contentRepositoryIdentifier = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryIdentifier;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);

        $workspace = $contentRepository->getWorkspaceFinder()
            ->findOneByName(WorkspaceName::fromString($workspaceName));
        assert($workspace instanceof Workspace);

        $sourceSubgraph = $contentRepository->getContentGraph()
            ->getSubgraph(
                $workspace->getCurrentContentStreamIdentifier(),
                DimensionSpacePoint::fromLegacyDimensionArray($sourceDimensions),
                VisibilityConstraints::withoutRestrictions()
            );

        $targetSubgraph = $contentRepository->getContentGraph()
            ->getSubgraph(
                $workspace->getCurrentContentStreamIdentifier(),
                DimensionSpacePoint::fromLegacyDimensionArray($dimensions),
                VisibilityConstraints::withoutRestrictions()
            );

        if ($mode === 'adoptFromAnotherDimension' || $mode === 'adoptFromAnotherDimensionAndCopyContent') {
            $this->adoptNodeAndParents(
                $identifier,
                $sourceSubgraph,
                $targetSubgraph,
                $contentRepository,
                $mode === 'adoptFromAnotherDimensionAndCopyContent'
            );

            $this->redirect('show', null, null, [
                'identifier' => $identifier,
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
        NodeAggregateIdentifier $identifier,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        ContentRepository $contentRepository
    ): void {
        $contentGraph = $contentRepository->getContentGraph();
        $nodeTypeManager = $contentRepository->getNodeTypeManager();
        $nodeAggregate = $contentGraph->findNodeAggregateByIdentifier($contentStreamIdentifier, $identifier);

        if ($nodeAggregate && $nodeAggregate->getCoveredDimensionSpacePoints()->count() > 0) {
            $this->response->setHttpHeader('X-Neos-Node-Exists-In-Other-Dimensions', 'true');

            // If the node exists in another dimension, we want to know how many nodes in the rootline are also
            // missing for the target dimension. This is needed in the UI to tell the user if nodes will be
            // materialized recursively upwards in the rootline. To find the node path for the given identifier,
            // we just use the first result. This is a safe assumption at least for "Document" nodes (aggregate=true),
            // because they are always moved in-sync.
            if ($nodeTypeManager->getNodeType($nodeAggregate->getNodeTypeName()->getValue())->isAggregate()) {
                // TODO: we would need the SourceDimensions parameter (as in Create()) to ensure the correct
                // rootline is traversed. Here, we, as a workaround, simply use the 1st aggregate for now.

                $missingNodesOnRootline = 0;
                while (
                    $parentAggregate = self::firstNodeAggregate(
                        $contentGraph->findParentNodeAggregates($contentStreamIdentifier, $identifier)
                    )
                ) {
                    if (!$parentAggregate->coversDimensionSpacePoint($dimensionSpacePoint)) {
                        $missingNodesOnRootline++;
                    }

                    $identifier = $parentAggregate->getIdentifier();
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
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param ContentSubgraphInterface $sourceSubgraph
     * @param ContentSubgraphInterface $targetSubgraph
     * @param ContentRepository $contentRepository
     * @param boolean $copyContent true if the content from the nodes that are translated should be copied
     * @return void
     */
    protected function adoptNodeAndParents(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ContentSubgraphInterface $sourceSubgraph,
        ContentSubgraphInterface $targetSubgraph,
        ContentRepository $contentRepository,
        bool $copyContent
    ) {
        // TODO: IMPL COPYING OF CONTENT
        assert($copyContent === false); // TODO IMPL ME
        assert($sourceSubgraph->getContentStreamIdentifier()->equals($targetSubgraph->getContentStreamIdentifier()));

        $identifiersFromRootlineToTranslate = [];
        while (
            $nodeAggregateIdentifier
            && $targetSubgraph->findNodeByNodeAggregateIdentifier($nodeAggregateIdentifier) === null
        ) {
            $identifiersFromRootlineToTranslate[] = $nodeAggregateIdentifier;
            $nodeAggregateIdentifier = $sourceSubgraph->findParentNode($nodeAggregateIdentifier)
                ?->nodeAggregateIdentifier;
        }
        // $identifiersFromRootlineToTranslate is now bottom-to-top; so we need to reverse
        // them to know what we need to create.
        // TODO: TEST THAT AUTO CREATED CHILD NODES WORK (though this should not have influence)

        foreach (array_reverse($identifiersFromRootlineToTranslate) as $identifier) {
            assert($identifier instanceof NodeAggregateIdentifier);
            // TODO: do we REALLY need to block for every operation or do we find somethingmore clever?
            $contentRepository->handle(
                new CreateNodeVariant(
                    $sourceSubgraph->getContentStreamIdentifier(),
                    $identifier,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceSubgraph->getDimensionSpacePoint()),
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($targetSubgraph->getDimensionSpacePoint()),
                    UserIdentifier::forSystemUser() // TODO: USE THE CORRECT USER HERE
                )
            )->block();
        }
    }
}
