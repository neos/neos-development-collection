<?php
namespace Neos\ContentRepository\Migration\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Exception\MigrationException;
use Neos\ContentRepository\Migration\Filters\FilterInterface;
use Neos\ContentRepository\Migration\Filters\DoctrineFilterInterface;
use Neos\Flow\Persistence\Doctrine\Query;

/**
 * Service to determine if a given node matches a series of filters given by configuration.
 *
 * @Flow\Scope("singleton")
 */
class NodeFilter
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $filterConjunctions = [];

    /**
     * Return array with Doctrine expressions
     *
     * @param array $filterConfigurations
     * @param Query $baseQuery
     * @return array
     * @throws MigrationException
     */
    public function getFilterExpressions(array $filterConfigurations, Query $baseQuery): array
    {
        $filterExpressions = [];
        foreach ($filterConfigurations as $filterConfiguration) {
            $filterObject = $this->constructFilterObject($filterConfiguration);
            if ($filterObject instanceof DoctrineFilterInterface) {
                foreach ($filterObject->getFilterExpressions($baseQuery) as $filterExpression) {
                    $filterExpressions[] = $filterExpression;
                }
            }
        }
        return $filterExpressions;
    }

    /**
     * Apply local filters to result.
     *
     * @param NodeData $nodeData
     * @param array $filterConfiguration
     * @return boolean
     * @throws MigrationException
     */
    public function matchFilters(NodeData $nodeData, array $filterConfiguration)
    {
        $filterConjunction = $this->buildFilterConjunction($filterConfiguration);
        foreach ($filterConjunction as $filter) {
            if (!$filter->matches($nodeData)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $filterConfigurations
     * @return array<\Neos\ContentRepository\Migration\FilterInterface>
     * @throws MigrationException
     */
    protected function buildFilterConjunction(array $filterConfigurations)
    {
        $conjunctionIdentifier = md5(serialize($filterConfigurations));
        if (isset($this->filterConjunctions[$conjunctionIdentifier])) {
            return $this->filterConjunctions[$conjunctionIdentifier];
        }

        $conjunction = [];
        foreach ($filterConfigurations as $filterConfiguration) {
            $filterObject = $this->constructFilterObject($filterConfiguration);
            if ($filterObject instanceof FilterInterface) {
                $conjunction[] = $filterObject;
            }
        }
        $this->filterConjunctions[$conjunctionIdentifier] = $conjunction;

        return $conjunction;
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

        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('Neos\ContentRepository\Migration\Filters\\' . $name);
        if ($resolvedObjectName !== null) {
            return $resolvedObjectName;
        }

        throw new MigrationException('A filter with the name "' . $name . '" could not be found.', 1343199467);
    }
}
