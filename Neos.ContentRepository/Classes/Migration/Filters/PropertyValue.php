<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Migration\Filters;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Persistence\Exception\InvalidQueryException;

/**
 * Filter nodes having the given property and a matching value.
 */
class PropertyValue implements DoctrineFilterInterface
{
    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var string
     */
    protected $propertyValue;

    /**
     * Sets the property name to be checked.
     *
     * @param string $propertyName
     * @return void
     */
    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Sets the property value to be checked against.
     *
     * @param string $propertyValue
     * @return void
     */
    public function setPropertyValue(string $propertyValue): void
    {
        $this->propertyValue = $propertyValue;
    }

    /**
     * Filters for nodes having the property and value requested.
     *
     * @param Query $baseQuery
     * @return array
     * @throws InvalidQueryException
     */
    public function getFilterExpressions(Query $baseQuery): array
    {
        // Build the like parameter as "key": "value" to search by a specific key and value
        // See NodeDataRepository.findByProperties() for the "inspiration"
        $likeParameter = trim(json_encode(
            [$this->propertyName => $this->propertyValue],
            JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE
        ), "{}\n\t ");

        return [$baseQuery->like('properties', $likeParameter, false)];
    }
}
