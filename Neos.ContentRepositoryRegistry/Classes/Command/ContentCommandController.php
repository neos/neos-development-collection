<?php declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\CatchUpTriggerWithSynchronousOption;
use Neos\Flow\Cli\CommandController;

final class ContentCommandController extends CommandController
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
        parent::__construct();
    }

    /**
     * Refreshes the root node dimensions in the specified content repository for the specified workspace.
     *
     * In the content repository, the root node has to cover all existing dimension space points.
     * With this command, the root node can be updated such that it represents all configured dimensions
     *
     * @param string $contentRepository The content repository identifier. (Default: 'default')
     * @param string $workspace The workspace name. (Default: 'live')
     */
    public function refreshRootNodeDimensionsCommand(string $contentRepository = 'default', string $workspace = WorkspaceName::WORKSPACE_NAME_LIVE): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceInstance = $contentRepositoryInstance->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspace));
        if ($workspaceInstance === null) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspace]);
            $this->quit(1);
        }
        $this->outputLine('Refreshing root node dimensions in workspace <b>%s</b> (content repository <b>%s</b>)', [$workspaceInstance->workspaceName->value, $contentRepositoryId->value]);
        $this->outputLine('Resolved content stream <b>%s</b>', [$workspaceInstance->currentContentStreamId->value]);

        $rootNodeAggregates = $contentRepositoryInstance->getContentGraph($workspaceInstance->workspaceName)->findRootNodeAggregates(
            FindRootNodeAggregatesFilter::create()
        );

        foreach ($rootNodeAggregates as $rootNodeAggregate) {
            $this->outputLine('Refreshing dimensions for root node aggregate %s (of type %s)', [
                $rootNodeAggregate->nodeAggregateId->value,
                $rootNodeAggregate->nodeTypeName->value
            ]);
            $contentRepositoryInstance->handle(
                UpdateRootNodeAggregateDimensions::create(
                    $workspaceInstance->workspaceName,
                    $rootNodeAggregate->nodeAggregateId
                )
            )->block();
        }
        $this->outputLine('<success>Done!</success>');
    }

    /**
     * Moves a dimension space point from the source to the target in the specified workspace and content repository.
     *
     * With this command all nodes for a given content dimension can be moved to a different dimension. This can be necessary
     * if a dimension configuration has been added or renamed.
     *
     * *Note:* source and target dimensions have to be specified as JSON, for example:
     * ```
     * ./flow content:movedimensionspacepoint '{"language": "de"}' '{"language": "en"}'
     * ```
     *
     * @param string $source The JSON representation of the source dimension space point. (Example: '{"language": "de"}')
     * @param string $target The JSON representation of the target dimension space point. (Example: '{"language": "en"}')
     * @param string $contentRepository The content repository identifier. (Default: 'default')
     * @param string $workspace The workspace name. (Default: 'live')
     */
    public function moveDimensionSpacePointCommand(string $source, string $target, string $contentRepository = 'default', string $workspace = WorkspaceName::WORKSPACE_NAME_LIVE): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $sourceDimensionSpacePoint = DimensionSpacePoint::fromJsonString($source);
        $targetDimensionSpacePoint = DimensionSpacePoint::fromJsonString($target);

        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceInstance = $contentRepositoryInstance->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspace));
        if ($workspaceInstance === null) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspace]);
            $this->quit(1);
        }

        $this->outputLine('Moving <b>%s</b> to <b>%s</b> in workspace <b>%s</b> (content repository <b>%s</b>)', [$sourceDimensionSpacePoint->toJson(), $targetDimensionSpacePoint->toJson(), $workspaceInstance->workspaceName->value, $contentRepositoryId->value]);
        $this->outputLine('Resolved content stream <b>%s</b>', [$workspaceInstance->currentContentStreamId->value]);

        $contentRepositoryInstance->handle(
            MoveDimensionSpacePoint::create(
                $workspaceInstance->workspaceName,
                $sourceDimensionSpacePoint,
                $targetDimensionSpacePoint
            )
        )->block();
        $this->outputLine('<success>Done!</success>');
    }

    /**
     * Creates node variants recursively from the source to the target dimension space point in the specified workspace and content repository.
     *
     * This can be necessary if a new content dimension specialization was added (for example a more specific language)
     *
     * *Note:* source and target dimensions have to be specified as JSON, for example:
     * ```
     * ./flow content:createvariantsrecursively '{"language": "de"}' '{"language": "de_ch"}'
     * ```
     *
     * @param string $source The JSON representation of the source dimension space point. (Example: '{"language": "de"}')
     * @param string $target The JSON representation of the target origin dimension space point.  (Example: '{"language": "en"}')
     * @param string $contentRepository The content repository identifier. (Default: 'default')
     * @param string $workspace The workspace name. (Default: 'live')
     */
    public function createVariantsRecursivelyCommand(string $source, string $target, string $contentRepository = 'default', string $workspace = WorkspaceName::WORKSPACE_NAME_LIVE): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $sourceSpacePoint = DimensionSpacePoint::fromJsonString($source);
        $targetSpacePoint = OriginDimensionSpacePoint::fromJsonString($target);
        $workspaceName = WorkspaceName::fromString($workspace);

        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);

        try {
            $sourceSubgraph = $contentRepositoryInstance->getContentGraph($workspaceName)->getSubgraph(
                $sourceSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
        } catch (WorkspaceDoesNotExist) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspaceName->value]);
            $this->quit(1);
        }

        $this->outputLine('Creating <b>%s</b> to <b>%s</b> in workspace <b>%s</b> (content repository <b>%s</b>)', [$sourceSpacePoint->toJson(), $targetSpacePoint->toJson(), $workspaceName->value, $contentRepositoryId->value]);

        $rootNodeAggregates = $contentRepositoryInstance->getContentGraph($workspaceName)
            ->findRootNodeAggregates(FindRootNodeAggregatesFilter::create());


        foreach ($rootNodeAggregates as $rootNodeAggregate) {
            CatchUpTriggerWithSynchronousOption::synchronously(fn() =>
                $this->createVariantRecursivelyInternal(
                    0,
                    $rootNodeAggregate->nodeAggregateId,
                    $sourceSubgraph,
                    $targetSpacePoint,
                    $workspaceName,
                    $contentRepositoryInstance,
                )
            );
        }

        $this->outputLine('<success>Done!</success>');
    }

    private function createVariantRecursivelyInternal(int $level, NodeAggregateId $parentNodeAggregateId, ContentSubgraphInterface $sourceSubgraph, OriginDimensionSpacePoint $target, WorkspaceName $workspaceName, ContentRepository $contentRepository): void
    {
        $childNodes = $sourceSubgraph->findChildNodes(
            $parentNodeAggregateId,
            FindChildNodesFilter::create()
        );

        foreach ($childNodes as $childNode) {
            if ($childNode->classification->isRegular()) {
                $childNodeType = $contentRepository->getNodeTypeManager()->getNodeType($childNode->nodeTypeName);
                if ($childNodeType?->isOfType('Neos.Neos:Document')) {
                    $this->output("%s- %s\n", [
                        str_repeat('  ', $level),
                        $childNode->getProperty('uriPathSegment') ?? $childNode->nodeAggregateId->value
                    ]);
                }
                try {
                    // Tethered nodes' variants are automatically created when the parent is translated.
                    $contentRepository->handle(CreateNodeVariant::create(
                        $workspaceName,
                        $childNode->nodeAggregateId,
                        $childNode->originDimensionSpacePoint,
                        $target
                    ))->block();
                } catch (DimensionSpacePointIsAlreadyOccupied $e) {
                    if ($childNodeType?->isOfType('Neos.Neos:Document')) {
                        $this->output("%s  (already exists)\n", [
                            str_repeat('  ', $level)
                        ]);
                    }
                }
            }

            $this->createVariantRecursivelyInternal(
                $level + 1,
                $childNode->nodeAggregateId,
                $sourceSubgraph,
                $target,
                $workspaceName,
                $contentRepository
            );
        }
    }
}
