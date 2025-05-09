<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\NodeMigration\Filter\DimensionSpacePointsFilterFactory;
use Neos\ContentRepository\NodeMigration\Filter\FilterFactoryInterface;
use Neos\ContentRepository\NodeMigration\Filter\FiltersFactory;
use Neos\ContentRepository\NodeMigration\Filter\NodeNameFilterFactory;
use Neos\ContentRepository\NodeMigration\Filter\NodeTypeFilterFactory;
use Neos\ContentRepository\NodeMigration\Filter\PropertyNotEmptyFilterFactory;
use Neos\ContentRepository\NodeMigration\Filter\PropertyValueFilterFactory;
use Neos\ContentRepository\NodeMigration\Transformation\AddDimensionShineThroughTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\AddNewPropertyConverterAwareTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\ChangeNodeTypeTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\ChangePropertyValueConverterAwareTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\MoveDimensionSpacePointTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\PropertyConverterAwareTransformationFactoryInterface;
use Neos\ContentRepository\NodeMigration\Transformation\RemoveNodeTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\RemovePropertyTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\RenameNodeAggregateTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\RenamePropertyTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\StripTagsOnPropertyTransformationFactory;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationFactoryInterface;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationsFactory;
use Neos\ContentRepository\NodeMigration\Transformation\UpdateRootNodeAggregateDimensionsTransformationFactory;

/**
 * @implements ContentRepositoryServiceFactoryInterface<NodeMigrationService>
 */
final readonly class NodeMigrationServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    /**
     * @param array<string,class-string<FilterFactoryInterface>> $filterFactories
     * @param array<string,class-string<TransformationFactoryInterface|PropertyConverterAwareTransformationFactoryInterface>> $transformationFactories
     */
    public function __construct(
        private array $filterFactories,
        private array $transformationFactories
    ) {
    }

    public static function createDefault(): self
    {
        return new self(
            filterFactories: [
                'DimensionSpacePoints' => DimensionSpacePointsFilterFactory::class,
                'NodeName' => NodeNameFilterFactory::class,
                'NodeType' => NodeTypeFilterFactory::class,
                'PropertyNotEmpty' => PropertyNotEmptyFilterFactory::class,
                'PropertyValue' => PropertyValueFilterFactory::class,
            ],
            transformationFactories: [
                'AddDimensionShineThrough' => AddDimensionShineThroughTransformationFactory::class,
                'AddNewProperty' => AddNewPropertyConverterAwareTransformationFactory::class,
                'ChangeNodeType' => ChangeNodeTypeTransformationFactory::class,
                'ChangePropertyValue' => ChangePropertyValueConverterAwareTransformationFactory::class,
                'MoveDimensionSpacePoint' => MoveDimensionSpacePointTransformationFactory::class,
                'RemoveNode' => RemoveNodeTransformationFactory::class,
                'RemoveProperty' => RemovePropertyTransformationFactory::class,
                'RenameNodeAggregate' => RenameNodeAggregateTransformationFactory::class,
                'RenameProperty' => RenamePropertyTransformationFactory::class,
                'StripTagsOnProperty' => StripTagsOnPropertyTransformationFactory::class,
                'UpdateRootNodeAggregateDimensions' => UpdateRootNodeAggregateDimensionsTransformationFactory::class,
            ]
        );
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): NodeMigrationService
    {
        $filtersFactory = new FiltersFactory($serviceFactoryDependencies->contentRepository);
        foreach ($this->filterFactories as $filterIdentifier => $filterFactoryClassName) {
            $filtersFactory->registerFilter($filterIdentifier, new $filterFactoryClassName());
        }

        $transformationsFactory = new TransformationsFactory($serviceFactoryDependencies->contentRepository, $serviceFactoryDependencies->propertyConverter);
        foreach ($this->transformationFactories as $transformationIdentifier => $transformationFactoryClassName) {
            $transformationsFactory->registerTransformation($transformationIdentifier, new $transformationFactoryClassName());
        }

        return new NodeMigrationService(
            $serviceFactoryDependencies->contentRepository,
            $filtersFactory,
            $transformationsFactory
        );
    }
}
