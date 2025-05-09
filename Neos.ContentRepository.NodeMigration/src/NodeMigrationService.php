<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\NodeMigration\Command\ExecuteMigration;
use Neos\ContentRepository\NodeMigration\Filter\FiltersFactory;
use Neos\ContentRepository\NodeMigration\Filter\InvalidMigrationFilterSpecified;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationsFactory;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationSteps;

/**
 * Node Migrations are manually written adjustments to the Node tree;
 * stored in "Migrations/ContentRepository" in a package.
 *
 * They are used to transform properties on nodes, or change the dimension space points in the system to others.
 *
 * Internally, these migrations can be applied on three levels:
 *
 * - globally, like changing dimensions
 * - on a NodeAggregate, like changing a NodeAggregate type
 * - on a (materialized) Node, like changing node properties.
 *
 * In a single migration, only transformations belonging to a single "level" can be applied;
 * as otherwise, the order (and semantics) becomes non-obvious.
 *
 * All migrations are applied in an empty, new ContentStream,
 * which is forked off the target workspace where the migrations are done.
 * This way, migrations can be easily rolled back by discarding the content stream instead of publishing it.
 *
 * A migration file is structured like this:
 * migrations: [
 *   {filters: ... transformations: ...},
 *   {filters: ... transformations: ...}
 * ]
 *
 * Every pair of filters/transformations is a "submigration". Inside a submigration,
 * you'll operate on the result state of all *previous* submigrations;
 * but you do not see the modified state of the current submigration while you are running it.
 */
readonly class NodeMigrationService implements ContentRepositoryServiceInterface
{
    public function __construct(
        private ContentRepository $contentRepository,
        private FiltersFactory $filterFactory,
        private TransformationsFactory $transformationFactory
    ) {
    }

    public function executeMigration(ExecuteMigration $command): void
    {
        $sourceWorkspace = $this->contentRepository->findWorkspaceByName($command->sourceWorkspaceName);
        if ($sourceWorkspace === null) {
            throw new WorkspaceDoesNotExist(sprintf(
                'The workspace %s does not exist',
                $command->sourceWorkspaceName->value
            ), 1611688225);
        }

        $targetWorkspace = $this->contentRepository->findWorkspaceByName($command->targetWorkspaceName);
        if ($targetWorkspace?->hasPublishableChanges()) {
            throw new MigrationException(sprintf('Target workspace "%s" already exists an is not empty. Please clear the workspace before.', $targetWorkspace->workspaceName->value));
        }

        $transformationSteps = TransformationSteps::createEmpty();
        foreach ($command->migrationConfiguration->getMigration() as $migrationDescription) {
            $transformationSteps = $transformationSteps->merge($this->executeSubMigration(
                $migrationDescription,
                $command->sourceWorkspaceName,
                $command->targetWorkspaceName
            ));
        }

        if ($command->requireConfirmation) {
            $stepsThatRequireConfirmation = $transformationSteps->filterConfirmationRequired();
            if (!$stepsThatRequireConfirmation->isEmpty()) {
                throw NodeMigrationRequireConfirmationException::becauseStepsRequireConfirmation($stepsThatRequireConfirmation);
            }
        }

        if ($transformationSteps->isEmpty()) {
            throw new MigrationException('Migration did not issue any commands.', 1742117823);
        }

        $targetWorkspaceWasCreated = false;
        if ($targetWorkspace === null) {
            $this->contentRepository->handle(
                CreateWorkspace::create(
                    $command->targetWorkspaceName,
                    $sourceWorkspace->workspaceName,
                    $command->contentStreamId,
                )
            );
            $targetWorkspaceWasCreated = true;
        }

        foreach ($transformationSteps as $transformationStep) {
            foreach ($transformationStep->commands as $transformationCommand) {
                $this->contentRepository->handle($transformationCommand);
            }
        }

        if ($command->publishOnSuccess === true) {
            $this->contentRepository->handle(
                PublishWorkspace::create($command->targetWorkspaceName)
            );

            if ($targetWorkspaceWasCreated === true) {
                $this->contentRepository->handle(
                    DeleteWorkspace::create($command->targetWorkspaceName)
                );
            }
        }
    }

    /**
     * Execute a single "filters / transformation" pair, i.e. a single sub-migration
     *
     * @param array<string,mixed> $migrationDescription
     * @throws MigrationException
     */
    protected function executeSubMigration(
        array $migrationDescription,
        WorkspaceName $workspaceNameForReading,
        WorkspaceName $workspaceNameForWriting
    ): TransformationSteps {
        $filters = $this->filterFactory->buildFilterConjunction($migrationDescription['filters'] ?? []);
        $transformations = $this->transformationFactory->buildTransformation(
            $migrationDescription['transformations'] ?? []
        );

        if ($transformations->containsMoreThanOneTransformationType()) {
            throw new InvalidMigrationFilterSpecified('more than one transformation type', 1617389468);
        }

        if (
            $transformations->containsGlobal()
            && ($filters->containsNodeAggregateBased() || $filters->containsNodeBased())
        ) {
            throw new InvalidMigrationFilterSpecified(
                'Global transformations are only supported without any filters',
                1617389474
            );
        }

        if ($transformations->containsNodeAggregateBased() && $filters->containsNodeBased()) {
            throw new InvalidMigrationFilterSpecified(
                'NodeAggregate Based transformations are only supported without any node based filters',
                1617389479
            );
        }

        $transformationSteps = TransformationSteps::createEmpty();
        if ($transformations->containsGlobal()) {
            $transformationSteps = $transformationSteps->merge(
                $transformations->executeGlobal($workspaceNameForReading, $workspaceNameForWriting)
            );
        } elseif ($transformations->containsNodeAggregateBased()) {
            $contentGraph = $this->contentRepository->getContentGraph($workspaceNameForReading);
            foreach ($contentGraph->findUsedNodeTypeNames() as $nodeTypeName) {
                foreach (
                    $contentGraph->findNodeAggregatesByType(
                        $nodeTypeName
                    ) as $nodeAggregate
                ) {
                    if ($filters->matchesNodeAggregate($nodeAggregate)) {
                        $transformationSteps = $transformationSteps->merge(
                            $transformations->executeNodeAggregateBased($nodeAggregate, $workspaceNameForWriting)
                        );
                    }
                }
            }
        } elseif ($transformations->containsNodeBased()) {
            $contentGraph = $this->contentRepository->getContentGraph($workspaceNameForReading);
            foreach ($contentGraph->findUsedNodeTypeNames() as $nodeTypeName) {
                foreach (
                    $contentGraph->findNodeAggregatesByType(
                        $nodeTypeName
                    ) as $nodeAggregate
                ) {
                    /* @var $nodeAggregate NodeAggregate */
                    // we *also* apply the node-aggregate-based filters on the node based transformations,
                    // so that you can filter Nodes e.g. based on node type
                    if ($filters->matchesNodeAggregate($nodeAggregate)) {
                        foreach ($nodeAggregate->occupiedDimensionSpacePoints as $originDimensionSpacePoint) {
                            $node = $nodeAggregate->getNodeByOccupiedDimensionSpacePoint($originDimensionSpacePoint);
                            // The node at $contentStreamId and $originDimensionSpacePoint
                            // *really* exists at this point, and is no shine-through.

                            $coveredDimensionSpacePoints = $nodeAggregate->getCoverageByOccupant(
                                $originDimensionSpacePoint
                            );

                            if ($filters->matchesNode($node)) {
                                $transformationSteps = $transformationSteps->merge(
                                    $transformations->executeNodeBased(
                                        $node,
                                        $coveredDimensionSpacePoints,
                                        $workspaceNameForWriting
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }

        return $transformationSteps;
    }
}
