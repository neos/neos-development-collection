<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Migration;

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Migration\Domain\Model\MigrationConfiguration;
use Neos\ContentRepository\Migration\Exception\MigrationException;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Command\ForkContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\Migration\Filters\FilterFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Migration\Transformations\TransformationFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\WorkspaceDoesNotExist;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * Node Migrations are manually written adjustments to the Node tree; stored in "Migrations/ContentRepository" in a package.
 *
 * They are used to transform properties on nodes, or change the dimension space points in the system to others.
 *
 * Internally, these migrations can be applied on three levels:
 *
 * - globally, like changing dimensions
 * - on a NodeAggregate, like changing a NodeAggregate type
 * - on a (materialized) Node, like changing node properties.
 *
 * In a single migration, only transformations belonging to a single "level" can be applied; as otherwise, the order (and semantics)
 * becomes non-obvious.
 *
 * All migrations are applied in an empty, new ContentStream, which is forked off the target workspace where the
 * migrations are done. This way, migrations can be easily rolled back by the content stream instead of publishing it.
 *
 * @Flow\Scope("singleton")
 */
class NodeMigrationService
{
    protected WorkspaceFinder $workspaceFinder;
    protected ContentStreamCommandHandler $contentStreamCommandHandler;
    protected ContentGraphInterface $contentGraph;
    protected FilterFactory $filterFactory;
    protected TransformationFactory $transformationFactory;

    public function __construct(WorkspaceFinder $workspaceFinder, ContentStreamCommandHandler $contentStreamCommandHandler, ContentGraphInterface $contentGraph, FilterFactory $filterFactory, TransformationFactory $transformationFactory)
    {
        $this->workspaceFinder = $workspaceFinder;
        $this->contentStreamCommandHandler = $contentStreamCommandHandler;
        $this->contentGraph = $contentGraph;
        $this->filterFactory = $filterFactory;
        $this->transformationFactory = $transformationFactory;
    }

    public function execute(MigrationConfiguration $migrationConfiguration, WorkspaceName $workspaceName, ContentStreamIdentifier $contentStreamForWriting)
    {
        $workspace = $this->workspaceFinder->findOneByName($workspaceName);
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The workspace %s does not exist', $workspaceName), 1611688225);
        }

        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $contentStreamForWriting,
                $workspace->getCurrentContentStreamIdentifier()
            )
        )->blockUntilProjectionsAreUpToDate();

        foreach ($migrationConfiguration->getMigration() as $migrationDescription) {
            /** array $migrationDescription */
            $this->executeSingle($migrationDescription, $workspace->getCurrentContentStreamIdentifier(), $contentStreamForWriting)->blockUntilProjectionsAreUpToDate();
        }

        echo "TODO: adjusted to new content stream: " . $contentStreamForWriting;
    }

    /**
     * Execute a single migration
     *
     * @param array $migrationDescription
     * @param ContentStreamIdentifier $contentStreamForReading
     * @param ContentStreamIdentifier $contentStreamForWriting
     * @return void
     * @throws MigrationException
     */
    protected function executeSingle(array $migrationDescription, ContentStreamIdentifier $contentStreamForReading, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        $filters = $this->filterFactory->buildFilterConjunction($migrationDescription['filters']);
        $transformations = $this->transformationFactory->buildTransformation($migrationDescription['transformations']);

        if ($transformations->containsMoreThanOneTransformationType()) {
            throw new \Exception("TODO: more than one transformation type");
        }

        if ($transformations->containsGlobal() && ($filters->containsNodeAggregateBased() || $filters->containsNodeBased())) {
            throw new \Exception("TODO: Global transformations are only supported without any filters");
        }

        if ($transformations->containsNodeAggregateBased() && $filters->containsNodeBased()) {
            throw new \Exception("TODO: NodeAggregate Based transformations are only supported without any node based filters");
        }

        $commandResult = CommandResult::createEmpty();
        if ($transformations->containsGlobal()) {
            $commandResult = $transformations->executeGlobal($contentStreamForReading, $contentStreamForWriting);
        } else if ($transformations->containsNodeAggregateBased()) {
            foreach ($this->contentGraph->findProjectedNodeTypes() as $nodeTypeName) {
                foreach ($this->contentGraph->findNodeAggregatesByType($contentStreamForReading, $nodeTypeName) as $nodeAggregate) {
                    if ($filters->matchesNodeAggregate($nodeAggregate)) {
                        $commandResult = $commandResult->merge(
                            $transformations->executeNodeAggregateBased($nodeAggregate, $contentStreamForWriting)
                        );

                    }
                }
            }
        } else if ($transformations->containsNodeBased()) {
            foreach ($this->contentGraph->findProjectedNodeTypes() as $nodeTypeName) {
                foreach ($this->contentGraph->findNodeAggregatesByType($contentStreamForReading, $nodeTypeName) as $nodeAggregate) {
                    // we *also* apply the node-aggregate-based filters on the node based transformations, so that you can filter
                    // Nodes e.g. based on node type
                    if ($filters->matchesNodeAggregate($nodeAggregate)) {
                        foreach ($nodeAggregate->getOccupiedDimensionSpacePoints() as $originDimensionSpacePoint) {
                            $node = $nodeAggregate->getNodeByOccupiedDimensionSpacePoint($originDimensionSpacePoint);
                            // The node at $contentStreamIdentifier and $originDimensionSpacePoint *really* exists at this point,
                            // and is no shine-through.

                            if ($filters->matchesNode($node)) {
                                $commandResult = $commandResult->merge(
                                    $transformations->executeNodeBased($node, $contentStreamForWriting)
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
