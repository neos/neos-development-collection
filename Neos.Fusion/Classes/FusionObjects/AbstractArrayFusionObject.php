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
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->fusionValue($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
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
    protected function evaluateNestedProperties(?string $defaultFusionPrototypeName = null):array
    {
        $sortedChildFusionKeys = $this->sortNestedProperties();

        if (count($sortedChildFusionKeys) === 0) {
            return [];
        }

        $result = [];
        foreach ($sortedChildFusionKeys as $key) {
            $propertyPath = $key;
            if ($this->isUntyped($key) && $defaultFusionPrototypeName) {
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
     * Sort the Fusion objects inside $this->properties depending on:
     * - numerical ordering
     * - position meta-property
     *
     * This will ignore all properties defined in "@ignoreProperties" in Fusion
     *
     * @see PositionalArraySorter
     *
     * @return array an ordered list of key value pairs
     * @throws FusionException if the positional string has an unsupported format
     */
    protected function sortNestedProperties(): array
    {
        $arraySorter = new PositionalArraySorter($this->properties, '__meta.position');
        try {
            $sortedFusionKeys = $arraySorter->getSortedKeys();
        } catch (InvalidPositionException $exception) {
            throw new FusionException('Invalid position string', 1345126502, $exception);
        }

        foreach ($this->ignoreProperties as $ignoredPropertyName) {
            $key = array_search($ignoredPropertyName, $sortedFusionKeys);
            if ($key !== false) {
                unset($sortedFusionKeys[$key]);
            }
        }
        return $sortedFusionKeys;
    }

    /**
     * Returns TRUE if the given property has no object type assigned
     *
     * @param string $key fusion child key path to check
     * @return bool
     */
    protected function isUntyped(string $key): bool
    {
        $property = $this->properties[$key];
        if (!is_array($property)) {
            return false;
        }
        return !isset($property['__objectType']) && !isset($property['__eelExpression']) && !isset($property['__value']);
    }
}
