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

use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Utility\Exception\InvalidPositionException;
use Neos\Utility\PositionalArraySorter;
use Neos\Fusion;

/**
 * Fusion object to render and array of key value pairs by evaluating all properties
 */
class DataStructureImplementation extends AbstractArrayFusionObject
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function evaluate()
    {
        $sortedChildFusionKeys = $this->sortNestedFusionKeys();

        if (count($sortedChildFusionKeys) === 0) {
            return [];
        }

        $result = [];
        foreach ($sortedChildFusionKeys as $key) {
            $propertyPath = $key;
            if ($this->isUntypedProperty($this->properties[$key])) {
                $propertyPath .= '<Neos.Fusion:DataStructure>';
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
     * @throws Fusion\Exception if the positional string has an unsupported format
     */
    protected function sortNestedFusionKeys()
    {
        $arraySorter = new PositionalArraySorter($this->properties, '__meta.position');
        try {
            $sortedFusionKeys = $arraySorter->getSortedKeys();
        } catch (InvalidPositionException $exception) {
            throw new Fusion\Exception('Invalid position string', 1345126502, $exception);
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
     * @param mixed $property
     * @return bool
     */
    private function isUntypedProperty($property): bool
    {
        if (!is_array($property)) {
            return false;
        }
        return array_intersect_key(array_flip(Parser::$reservedParseTreeKeys), $property) === [];
    }
}
