<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;
/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Feature\Migration\Filter\DimensionSpacePointsFilterFactory;
use Neos\ContentRepository\Feature\Migration\Filter\FiltersFactory;
use Neos\ContentRepository\Feature\Migration\Filter\NodeNameFilterFactory;
use Neos\ContentRepository\Feature\Migration\Filter\NodeTypeFilterFactory;
use Neos\ContentRepository\Feature\Migration\Filter\PropertyValueFilterFactory;
use Neos\ContentRepository\Feature\Migration\MigrationCommandHandler;
use Neos\ContentRepository\Feature\Migration\Transformation\AddDimensionShineThroughTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\AddNewPropertyTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\ChangeNodeTypeTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\ChangePropertyValueTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\MoveDimensionSpacePointTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\RemoveNodeTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\RemovePropertyTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\RenameNodeAggregateTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\RenamePropertyTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\StripTagsOnPropertyTransformationFactory;
use Neos\ContentRepository\Feature\Migration\Transformation\TransformationsFactory;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

#[Flow\Scope('singleton')]
final class MigrationObjectFactory
{
    public function __construct(
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly WorkspaceCommandHandler $workspaceCommandHandler,
        private readonly ContentGraphInterface $contentGraph,
        private readonly ObjectManagerInterface $container,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly DimensionSpaceCommandHandler $dimensionSpaceCommandHandler,
        private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler
    )
    {
    }

    public function buildMigrationCommandHandler(): MigrationCommandHandler
    {
        return new MigrationCommandHandler(
            $this->workspaceFinder,
            $this->workspaceCommandHandler,
            $this->contentGraph,
            new FiltersFactory($this->container),
            new TransformationsFactory($this->container)
        );
    }

    public function buildDimensionSpacePointsFilterFactory(): DimensionSpacePointsFilterFactory
    {
        return new DimensionSpacePointsFilterFactory($this->interDimensionalVariationGraph);
    }

    public function buildNodeNameFilterFactory(): NodeNameFilterFactory
    {
        return new NodeNameFilterFactory();
    }

    public function buildNodeTypeFilterFactory(): NodeTypeFilterFactory
    {
        return new NodeTypeFilterFactory($this->nodeTypeManager);
    }

    public function buildPropertyValueFilterFactory(): PropertyValueFilterFactory
    {
        return new PropertyValueFilterFactory();
    }

    public function buildAddDimensionShineThroughTransformationFactory(): AddDimensionShineThroughTransformationFactory
    {
        return new AddDimensionShineThroughTransformationFactory($this->dimensionSpaceCommandHandler);
    }

    public function buildAddNewPropertyTransformationFactory(): AddNewPropertyTransformationFactory
    {
        return new AddNewPropertyTransformationFactory($this->nodeAggregateCommandHandler);
    }

    public function buildChangeNodeTypeTransformationFactory(): ChangeNodeTypeTransformationFactory
    {
        return new ChangeNodeTypeTransformationFactory($this->nodeAggregateCommandHandler);
    }

    public function buildChangePropertyValueTransformationFactory(): ChangePropertyValueTransformationFactory
    {
        return new ChangePropertyValueTransformationFactory($this->nodeAggregateCommandHandler);
    }

    public function buildMoveDimensionSpacePointTransformationFactory(): MoveDimensionSpacePointTransformationFactory
    {
        return new MoveDimensionSpacePointTransformationFactory($this->dimensionSpaceCommandHandler);
    }

    public function buildRemoveNodeTransformationFactory(): RemoveNodeTransformationFactory
    {
        return new RemoveNodeTransformationFactory($this->nodeAggregateCommandHandler);
    }

    public function buildRemovePropertyTransformationFactory(): RemovePropertyTransformationFactory
    {
        return new RemovePropertyTransformationFactory($this->nodeAggregateCommandHandler);
    }

    public function buildRenameNodeAggregateTransformationFactory(): RenameNodeAggregateTransformationFactory
    {
        return new RenameNodeAggregateTransformationFactory($this->nodeAggregateCommandHandler);
    }

    public function buildRenamePropertyTransformationFactory(): RenamePropertyTransformationFactory
    {
        return new RenamePropertyTransformationFactory($this->nodeAggregateCommandHandler);
    }

    public function buildStripTagsOnPropertyTransformationFactory(): StripTagsOnPropertyTransformationFactory
    {
        return new StripTagsOnPropertyTransformationFactory($this->nodeAggregateCommandHandler);
    }
}
