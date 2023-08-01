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
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
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

        $rootNodeAggregates = $contentRepositoryInstance->getContentGraph()->findRootNodeAggregates(
            $workspaceInstance->currentContentStreamId,
            FindRootNodeAggregatesFilter::create()
        );

        foreach ($rootNodeAggregates as $rootNodeAggregate) {
            $this->outputLine('Refreshing dimensions for root node aggregate %s (of type %s)', [
                $rootNodeAggregate->nodeAggregateId->value,
                $rootNodeAggregate->nodeTypeName->value
            ]);
            $contentRepositoryInstance->handle(
                new UpdateRootNodeAggregateDimensions(
                    $workspaceInstance->currentContentStreamId,
                    $rootNodeAggregate->nodeAggregateId
                )
            )->block();
        }
        $this->outputLine('<success>Done!</success>');
    }

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
            new MoveDimensionSpacePoint(
                $workspaceInstance->currentContentStreamId,
                $sourceDimensionSpacePoint,
                $targetDimensionSpacePoint
            )
        )->block();
        $this->outputLine('<success>Done!</success>');
    }

    public function createVariantsRecursivelyCommand(string $source, string $target, string $contentRepository = 'default', string $workspace = WorkspaceName::WORKSPACE_NAME_LIVE): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $sourceSpacePoint = DimensionSpacePoint::fromJsonString($source);
        $targetSpacePoint = OriginDimensionSpacePoint::fromJsonString($target);

        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceInstance = $contentRepositoryInstance->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspace));
        if ($workspaceInstance === null) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspace]);
            $this->quit(1);
        }

        $this->outputLine('Creating <b>%s</b> to <b>%s</b> in workspace <b>%s</b> (content repository <b>%s</b>)', [$sourceSpacePoint->toJson(), $targetSpacePoint->toJson(), $workspaceInstance->workspaceName->value, $contentRepositoryId->value]);
        $this->outputLine('Resolved content stream <b>%s</b>', [$workspaceInstance->currentContentStreamId->value]);

        $sourceSubgraph = $contentRepositoryInstance->getContentGraph()->getSubgraph(
            $workspaceInstance->currentContentStreamId,
            $sourceSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );

        $rootNodeAggregates = $contentRepositoryInstance->getContentGraph()
            ->findRootNodeAggregates($workspaceInstance->currentContentStreamId, FindRootNodeAggregatesFilter::create());


        foreach ($rootNodeAggregates as $rootNodeAggregate) {
            CatchUpTriggerWithSynchronousOption::synchronously(fn() =>
                $this->createVariantRecursivelyInternal(
                    0,
                    $rootNodeAggregate->nodeAggregateId,
                    $sourceSubgraph,
                    $targetSpacePoint,
                    $workspaceInstance->currentContentStreamId,
                    $contentRepositoryInstance,
                )
            );
        }

        $this->outputLine('<success>Done!</success>');
    }

    private function createVariantRecursivelyInternal(int $level, NodeAggregateId $parentNodeAggregateId, ContentSubgraphInterface $sourceSubgraph, OriginDimensionSpacePoint $target, ContentStreamId $contentStreamId, ContentRepository $contentRepository) {
        $childNodes = $sourceSubgraph->findChildNodes(
            $parentNodeAggregateId,
            FindChildNodesFilter::create()
        );

        foreach ($childNodes as $childNode) {
            if ($childNode->classification->isRegular()) {
                if ($childNode->nodeType->isOfType('Neos.Neos:Document')) {
                    $this->output("%s- %s\n", [
                        str_repeat('  ', $level),
                        $childNode->getProperty('uriPathSegment') ?? $childNode->nodeAggregateId->value
                    ]);
                }
                try {
                    // Tethered nodes' variants are automatically created when the parent is translated.
                    $contentRepository->handle(new CreateNodeVariant(
                        $contentStreamId,
                        $childNode->nodeAggregateId,
                        $childNode->originDimensionSpacePoint,
                        $target
                    ))->block();
                } catch (DimensionSpacePointIsAlreadyOccupied $e) {
                    if ($childNode->nodeType->isOfType('Neos.Neos:Document')) {
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
                $contentStreamId,
                $contentRepository
            );
        }
    }
}
