<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Filter;

use Neos\ContentRepository\Feature\Migration\MigrationCommandHandler;
use Neos\ContentRepository\Feature\Migration\MigrationException;
use Psr\Container\ContainerInterface;

/**
 * Implementation detail of {@see MigrationCommandHandler}
 */
class FiltersFactory
{

    public function __construct(
        private readonly ContainerInterface $container
    )
    {
    }

    /**
     * @param array<int,array<string,mixed>> $filterConfigurations
     * @throws MigrationException
     */
    public function buildFilterConjunction(array $filterConfigurations): Filters
    {
        $filterObjects = [];
        foreach ($filterConfigurations as $filterConfiguration) {
            $filterObjects[] = $this->constructFilterObject($filterConfiguration);
        }

        return new Filters($filterObjects);
    }

    /**
     * @param array<string,mixed> $filterConfiguration
     */
    protected function constructFilterObject(
        array $filterConfiguration
    ): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface {
        $filterFactoryClassName = $this->resolveFilterFactoryClass($filterConfiguration['type']);

        $filterFactory = $this->container->get($filterFactoryClassName);
        assert($filterFactory instanceof FilterFactoryInterface);
        return $filterFactory->build($filterConfiguration['settings'] ?? []);
    }

    /**
     * Resolves the class name for the filter by first assuming it is a full qualified class name
     * and otherwise searching in this package (so filters delivered in Neos.ContentRepository can be used
     * by simply giving the class name without namespace).
     *
     * @throws MigrationException
     */
    protected function resolveFilterFactoryClass(string $name): string
    {
        if (class_exists($name)) {
            return $name;
        }

        if ($name === 'DimensionValues') {
            throw new MigrationException(
                'The "DimensionValues" filter from the legacy content repository has been replaced'
                    . ' by the "DimensionSpacePoint" filter in the event-sourced content repository.'
                    . ' Please adjust your node migrations.',
                1637177939
            );
        }

        if ($name === 'IsRemoved') {
            throw new MigrationException(
                'The "IsRemoved" filter from the legacy content repository has been removed without replacement,'
                    . ' as removed nodes are not resolved during a migration anymore.',
                1637177986
            );
        }

        if ($name === 'Workspace') {
            throw new MigrationException(
                'The "Workspace" filter from the legacy content repository has been removed without replacement,'
                    . ' as migrations are always targeting a single workspace (live by default).',
                1637178056
            );
        }

        return 'Neos\ContentRepository\Feature\Migration\Filter\\' . $name . 'FilterFactory';
    }
}
