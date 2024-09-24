<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Exception\InvalidPositionException;
use Neos\Utility\PositionalArraySorter;
use Neos\Fusion\Exception as FusionException;
use Neos\Fusion\Core\Runtime;

/**
 * Base class for Fusion objects that need access to arbitrary properties, like DataStructureImplementation.
 */
abstract class AbstractArrayFusionObject extends AbstractFusionObject implements \ArrayAccess
{
    /**
     * List of properties which have been set using array access. We store this for *every* Fusion object
     * in order to do things like:
     * x = Foo {
     *   a = 'foo'
     *   b = ${this.a + 'bar'}
     * }
     *
     * @var array
     * @internal
     */
    protected $properties = [];

    /**
     * If you iterate over "properties" these in here should usually be ignored. For example additional properties in "Case" that are not "Matchers".
     *
     * @var array
     */
    protected $ignoreProperties = [];

    /**
     * @param array $ignoreProperties
     * @return void
     */
    public function setIgnoreProperties($ignoreProperties = [])
    {
        $this->ignoreProperties = $ignoreProperties;
    }

    /**
     * Checks wether this Array fusion object should have sorted properties (according to __meta.position) or not
     *
     * @return bool
     * @see applyPositionalArraySorterToProperties
     */
    public function shouldSortProperties(): bool
    {
        $sortProperties = $this->fusionValue('__meta/sortProperties');
        if ($sortProperties !== null) {
            return (boolean)$sortProperties;
        }

        return true;
    }

    /**
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset): bool
    {
        return isset($this->properties[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->fusionValue($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->properties[$offset] = $value;
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->properties[$offset]);
    }

    /**
     * @param string|null $defaultFusionPrototypeName
     * @return array
     * @throws FusionException
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationException
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Security\Exception
     */
    protected function evaluateNestedProperties(?string $defaultFusionPrototypeName = null): array
    {
        $sortedChildFusionKeys = $this->preparePropertyKeys($this->properties, $this->ignoreProperties);

        if (count($sortedChildFusionKeys) === 0) {
            return [];
        }

        $result = [];
        foreach ($sortedChildFusionKeys as $key) {
            if ($this->isUnset($key)) {
                continue;
            }
            $propertyPath = $key;
            if ($defaultFusionPrototypeName !== null && $this->isUntyped($key)) {
                $propertyPath .= '<' . $defaultFusionPrototypeName . '>';
            }
            try {
                $value = $this->fusionValue($propertyPath);
            } catch (\Exception $e) {
                $value = $this->runtime->handleRenderingException($this->path . '/' . $key, $e);
            }
            if ($value === null && $this->runtime->getLastEvaluationStatus() === Runtime::EVALUATION_SKIPPED) {
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param array $properties
     * @param array $ignoredProperties
     * @return array<string> Fusion keys in this Array fusion object
     * @throws FusionException
     */
    protected function preparePropertyKeys(array $properties, array $ignoredProperties): array
    {
        $properties = $this->filterIgnoredProperties($properties, $ignoredProperties);
        $maybeSortedFusionKeys = $this->shouldSortProperties() ? $this->applyPositionalArraySorterToProperties($properties) : array_keys($properties);
        return array_values($maybeSortedFusionKeys);
    }

    /**
     * @param array $properties
     * @return array
     * @throws FusionException
     */
    protected function applyPositionalArraySorterToProperties(array $properties): array
    {
        $arraySorter = new PositionalArraySorter($properties, '__meta.position');
        try {
            $sortedFusionKeys = $arraySorter->getSortedKeys();
        } catch (InvalidPositionException $exception) {
            throw new FusionException('Invalid position string', 1345126502, $exception);
        }

        return $sortedFusionKeys;
    }

    /**
     * Filters properties by ignoredProperties
     *
     * @param array $properties
     * @param array $ignoredProperties
     * @return array
     */
    protected function filterIgnoredProperties(array $properties, array $ignoredProperties): array
    {
        foreach ($ignoredProperties as $ignoredPropertyName) {
            unset($properties[$ignoredPropertyName]);
        }

        return $properties;
    }

    /**
     * Returns TRUE if the given fusion key has been removed via ">"
     *
     * @param string|int $key fusion child key path to check
     * @return bool
     */
    protected function isUnset(string|int $key): bool
    {
        return $this->properties[$key] === ['__stopInheritanceChain' => true];
    }

    /**
     * Returns TRUE if the given fusion key has no type, meaning neither
     * having a fusion objectType, eelExpression or value
     *
     * @param string|int $key fusion child key path to check
     * @return bool
     */
    protected function isUntyped(string|int $key): bool
    {
        $property = $this->properties[$key];
        if (!is_array($property)) {
            return false;
        }
        return !array_key_exists('__objectType', $property)
            && !array_key_exists('__eelExpression', $property)
            && !array_key_exists('__value', $property);
    }
}
