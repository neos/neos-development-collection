<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\Migration\Filter\InvalidMigrationFilterSpecified;
use Neos\ContentRepository\Migration\Exception\MigrationException;
use Neos\ContentRepository\Feature\Migration\Command\ExecuteMigration;
use Neos\ContentRepository\Feature\Migration\Filter\FilterFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\TransformationFactory;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Feature\Common\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\Flow\Annotations as Flow;

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
 *
 * @Flow\Scope("singleton")
 */
class MigrationCommandHandler
{
    protected WorkspaceFinder $workspaceFinder;
    protected WorkspaceCommandHandler $contentStreamCommandHandler;
    protected ContentGraphInterface $contentGraph;
    protected FilterFactory $filterFactory;
    protected TransformationFactory $transformationFactory;

    public function __construct(
        WorkspaceFinder $workspaceFinder,
        WorkspaceCommandHandler $contentStreamCommandHandler,
        ContentGraphInterface $contentGraph,
        FilterFactory $filterFactory,
        TransformationFactory $transformationFactory
    ) {
        $this->workspaceFinder = $workspaceFinder;
        $this->contentStreamCommandHandler = $contentStreamCommandHandler;
        $this->contentGraph = $contentGraph;
        $this->filterFactory = $filterFactory;
        $this->transformationFactory = $transformationFactory;
    }

    public function handleExecuteMigration(ExecuteMigration $command): void
    {
        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf(
                'The workspace %s does not exist',
                $command->getWorkspaceName()
            ), 1611688225);
        }

        $contentStreamForReading = $workspace->getCurrentContentStreamIdentifier();
        // TODO: I believe the logic for submigrations is not yet fully working
        foreach ($command->getMigrationConfiguration()->getMigration() as $step => $migrationDescription) {
            $contentStreamForWriting = $command->getOrCreateContentStreamIdentifierForWriting($step);
            $this->contentStreamCommandHandler->handleCreateWorkspace(
                new CreateWorkspace(
                    WorkspaceName::fromString($contentStreamForWriting->jsonSerialize()),
                    $workspace->getWorkspaceName(),
                    WorkspaceTitle::fromString($contentStreamForWriting->jsonSerialize()),
                    WorkspaceDescription::fromString(''),
                    UserIdentifier::forSystemUser(),
                    $contentStreamForWriting,
                )
            )->blockUntilProjectionsAreUpToDate();
            /** array $migrationDescription */
            $this->executeSubMigration(
                $migrationDescription,
                $workspace->getCurrentContentStreamIdentifier(),
                $contentStreamForWriting
            )->blockUntilProjectionsAreUpToDate();

            // TODO: WORKSPACE NAME pass through
            $contentStreamForReading = $contentStreamForWriting;
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
        ContentStreamIdentifier $contentStreamForReading,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        $filters = $this->filterFactory->buildFilterConjunction($migrationDescription['filters'] ?? []);
        $transformations = $this->transformationFactory->buildTransformation(
            $migrationDescription['transformations'] ?? []
        );

        if ($transformations->containsMoreThanOneTransformationType()) {
            throw new InvalidMigrationFilterSpecified('more than one transformation type', 1617389468);
        }

        if ($transformations->containsGlobal()
            && ($filters->containsNodeAggregateBased() || $filters->containsNodeBased())) {
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

        $commandResult = CommandResult::createEmpty();
        if ($transformations->containsGlobal()) {
            $commandResult = $transformations->executeGlobal($contentStreamForReading, $contentStreamForWriting);
        } elseif ($transformations->containsNodeAggregateBased()) {
            foreach ($this->contentGraph->findUsedNodeTypeNames() as $nodeTypeName) {
                foreach ($this->contentGraph->findNodeAggregatesByType(
                    $contentStreamForReading,
                    $nodeTypeName
                ) as $nodeAggregate) {
                    if ($filters->matchesNodeAggregate($nodeAggregate)) {
                        $commandResult = $commandResult->merge(
                            $transformations->executeNodeAggregateBased($nodeAggregate, $contentStreamForWriting)
                        );
                    }
                }
            }
        } elseif ($transformations->containsNodeBased()) {
            foreach ($this->contentGraph->findUsedNodeTypeNames() as $nodeTypeName) {
                foreach ($this->contentGraph->findNodeAggregatesByType(
                    $contentStreamForReading,
                    $nodeTypeName
                ) as $nodeAggregate) {
                    // we *also* apply the node-aggregate-based filters on the node based transformations,
                    // so that you can filter Nodes e.g. based on node type
                    if ($filters->matchesNodeAggregate($nodeAggregate)) {
                        foreach ($nodeAggregate->getOccupiedDimensionSpacePoints() as $originDimensionSpacePoint) {
                            $node = $nodeAggregate->getNodeByOccupiedDimensionSpacePoint($originDimensionSpacePoint);
                            // The node at $contentStreamIdentifier and $originDimensionSpacePoint
                            // *really* exists at this point, and is no shine-through.

                            $coveredDimensionSpacePoints = $nodeAggregate->getCoverageByOccupant(
                                $originDimensionSpacePoint
                            );

                            if ($filters->matchesNode($node)) {
                                $commandResult = $commandResult->merge(
                                    $transformations->executeNodeBased(
                                        $node,
                                        $coveredDimensionSpacePoints,
                                        $contentStreamForWriting
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }
        return $commandResult;
    }
}
