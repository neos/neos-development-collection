<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Filter;

use Neos\ContentRepository\Feature\Migration\MigrationCommandHandler;
use Neos\ContentRepository\Feature\Migration\MigrationException;
use Neos\ContentRepository\Feature\Migration\Filter\NodeAggregateBasedFilterInterface;
use Neos\ContentRepository\Feature\Migration\Filter\NodeBasedFilterInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * Implementation detail of {@see MigrationCommandHandler}
 *
 * @Flow\Scope("singleton")
 */
class FilterFactory
{
    protected ObjectManagerInterface $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        // TODO: decouple from ObjectManager!
        $this->objectManager = $objectManager;
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
     * @throws MigrationException
     */
    protected function constructFilterObject(
        array $filterConfiguration
    ): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface {
        $filterClassName = $this->resolveFilterClass($filterConfiguration['type']);
        $filter = new $filterClassName;
        if (!$filter instanceof NodeAggregateBasedFilterInterface && !$filter instanceof NodeBasedFilterInterface) {
            throw new \InvalidArgumentException(
                'Given filter ' . $filter
                    . ' does not implement NodeAggregateBasedFilterInterface or NodeBasedFilterInterface.',
                1645391476
            );
        }
        foreach ($filterConfiguration['settings'] as $propertyName => $propertyValue) {
            $setterName = 'set' . ucfirst($propertyName);
            if (method_exists($filter, $setterName)) {
                $filter->$setterName($propertyValue);
            } else {
                throw new MigrationException(
                    'Filter "' . $filterClassName . '" does not have a setter for "' . $propertyName
                        . '", so maybe it is not supported.',
                    1343199531
                );
            }
        }

        return $filter;
    }

    /**
     * Resolves the class name for the filter by first assuming it is a full qualified class name
     * and otherwise searching in this package (so filters delivered in Neos.ContentRepository can be used
     * by simply giving the class name without namespace).
     *
     * @throws MigrationException
     */
    protected function resolveFilterClass(string $name): string
    {
        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($name);
        if ($resolvedObjectName !== null) {
            return $resolvedObjectName;
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

        $possibleFullFilterName = 'Neos\ContentRepository\Feature\Migration\Filter\\' . $name;
        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleFullFilterName);
        if ($resolvedObjectName !== null) {
            return $resolvedObjectName;
        }

        throw new MigrationException(
            'A filter with the name "' . $name . '" or "' . $possibleFullFilterName . '" could not be found.',
            1343199467
        );
    }
}
