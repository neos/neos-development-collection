<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Migration\Filters;

use Neos\ContentRepository\Migration\Exception\MigrationException;
use Neos\ContentRepository\Migration\Filters\DoctrineFilterInterface;
use Neos\ContentRepository\Migration\Filters\FilterInterface;
use Neos\EventSourcedContentRepository\Migration\Dto\Filters;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * Implementation detail of {@see MigrationCommandHandler}
 *
 * @Flow\Scope("singleton")
 */
class FilterFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $filterConfigurations
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
     * @param array $filterConfiguration
     * @return FilterInterface|DoctrineFilterInterface
     * @throws MigrationException
     */
    protected function constructFilterObject($filterConfiguration)
    {
        $filterClassName = $this->resolveFilterClass($filterConfiguration['type']);
        $filter = new $filterClassName;
        foreach ($filterConfiguration['settings'] as $propertyName => $propertyValue) {
            $setterName = 'set' . ucfirst($propertyName);
            if (method_exists($filter, $setterName)) {
                $filter->$setterName($propertyValue);
            } else {
                throw new MigrationException('Filter "' . $filterClassName . '" does not have a setter for "' . $propertyName . '", so maybe it is not supported.', 1343199531);
            }
        }

        return $filter;
    }

    /**
     * Resolves the class name for the filter by first assuming it is a full qualified class name and otherwise searching
     * in this package (so filters delivered in Neos.ContentRepository can be used by simply giving the class name without namespace).
     *
     * @param string $name
     * @return string
     * @throws MigrationException
     */
    protected function resolveFilterClass($name)
    {
        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($name);
        if ($resolvedObjectName !== null) {
            return $resolvedObjectName;
        }

        if ($name === 'DimensionValues') {
            throw new MigrationException('The "DimensionValues" filter from the legacy content repository has been replaced by the "DimensionSpacePoint" filter in the event-sourced content repository. Please adjust your node migrations.', 1637177939);
        }

        if ($name === 'IsRemoved') {
            throw new MigrationException('The "IsRemoved" filter from the legacy content repository has been removed without replacement, as removed nodes are not resolved during a migration anymore.', 1637177986);
        }

        if ($name === 'Workspace') {
            throw new MigrationException('The "Workspace" filter from the legacy content repository has been removed without replacement, as migrations are always targeting a single workspace (live by default).', 1637178056);
        }

        $possibleFullFilterName = 'Neos\EventSourcedContentRepository\Migration\Filters\\' . $name;
        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleFullFilterName);
        if ($resolvedObjectName !== null) {
            return $resolvedObjectName;
        }

        throw new MigrationException('A filter with the name "' . $name . '" or "' . $possibleFullFilterName . '" could not be found.', 1343199467);
    }
}
