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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
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

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspace));

        $this->outputFormatted('Refreshing root node dimensions in workspace %s (content repository %s)', [$workspace->workspaceName->name, $contentRepositoryId->value]);
        $this->outputFormatted('Resolved content stream %s', [$workspace->currentContentStreamId]);

        $rootNodeAggregates = $contentRepository->getContentGraph()->findRootNodeAggregates(
            $workspace->currentContentStreamId,
            FindRootNodeAggregatesFilter::create()
        );

        foreach ($rootNodeAggregates as $rootNodeAggregate) {
            $this->outputFormatted('Refreshing dimensions for root node aggregate %s (of type %s)', [
                $rootNodeAggregate->nodeAggregateId->value,
                $rootNodeAggregate->nodeTypeName->value
            ]);
            $contentRepository->handle(
                new UpdateRootNodeAggregateDimensions(
                    $workspace->currentContentStreamId,
                    $rootNodeAggregate->nodeAggregateId
                )
            )->block();
        }
        $this->outputFormatted('Done!');
    }

    public function moveDimensionSpacePointCommand(string $source, string $target, string $contentRepository = 'default', string $workspace = WorkspaceName::WORKSPACE_NAME_LIVE): void
    {
        // TODO: CLI arguments: $contentRepositoryId => $contentRepository (in other CLI commands)
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $source = DimensionSpacePoint::fromJsonString($source);
        $target = DimensionSpacePoint::fromJsonString($target);

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspace));

        $this->outputFormatted('Moving %s to %s in workspace %s (content repository %s)', [$source, $target, $workspace->workspaceName, $contentRepositoryId]);
        $this->outputFormatted('Resolved content stream %s', [$workspace->currentContentStreamId]);

        $contentRepository->handle(
            new MoveDimensionSpacePoint(
                $workspace->currentContentStreamId,
                $source,
                $target
            )
        )->block();
        $this->outputFormatted('Done!');
    }

    public function copyAcrossDimensions(string $source, string $target, string $contentRepository = 'default', string $workspace = WorkspaceName::WORKSPACE_NAME_LIVE): void
    {
        /*// TODO: CLI arguments: $contentRepositoryId => $contentRepository (in other CLI commands)
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $source = DimensionSpacePoint::fromJsonString($source);
        $target = DimensionSpacePoint::fromJsonString($target);

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspace));

        $this->outputFormatted('Moving %s to %s in workspace %s (content repository %s)', [$source, $target, $workspace, $contentRepository]);
        $this->outputFormatted('Resolved content stream %s', [$workspace->currentContentStreamId]);

        $contentRepository->handle(
            new MoveDimensionSpacePoint(
                $workspace->currentContentStreamId,
                $source,
                $target
            )
        )->block();
        $this->outputFormatted('Done!');*/
        // TODO
    }
}
