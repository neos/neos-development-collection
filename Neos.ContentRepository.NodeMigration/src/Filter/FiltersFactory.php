<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Filter;

use Neos\ContentRepository\NodeMigration\NodeMigrationService;
use Neos\ContentRepository\NodeMigration\MigrationException;

/**
 * Implementation detail of {@see NodeMigrationService}
 */
class FiltersFactory
{
    /**
     * @var array<string,FilterFactoryInterface>
     */
    private array $filterFactories = [];

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

    public function registerFilter(string $filterIdentifier, FilterFactoryInterface $filterFactory): self
    {
        $this->filterFactories[$filterIdentifier] = $filterFactory;
        return $this;
    }

    /**
     * @param array<string,mixed> $filterConfiguration
     */
    protected function constructFilterObject(
        array $filterConfiguration
    ): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface
    {
        $filterFactory = $this->resolveFilterFactory($filterConfiguration['type']);
        return $filterFactory->build($filterConfiguration['settings'] ?? []);
    }

    /**
     * Resolves the class name for the filter by first assuming it is a full qualified class name
     * and otherwise searching in this package (so filters delivered in Neos.ContentRepository can be used
     * by simply giving the class name without namespace).
     *
     * @throws MigrationException
     */
    protected function resolveFilterFactory(string $name): FilterFactoryInterface
    {
        if (isset($this->filterFactories[$name])) {
            return $this->filterFactories[$name];
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

        throw new MigrationException(
            'The "' . $name . '" filter is not registered.',
            1659603426
        );

    }
}
