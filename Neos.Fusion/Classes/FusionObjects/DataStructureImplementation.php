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
            try {
                if (is_array($this->properties[$key])
                    && !array_key_exists('__objectType', $this->properties[$key])
                    && !array_key_exists('__eelExpression', $this->properties[$key])
                    && !array_key_exists('__value', $this->properties[$key])
                    && !array_key_exists('__stopInheritanceChain', $this->properties[$key])
                ) {
                    $fullPath = $this->path . '/' . $key;
                    $value = $this->runtime->evaluate($fullPath . '<Neos.Fusion:DataStructure>', $this);
                } else {
                    $value = $this->fusionValue($key);
                }
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
}
