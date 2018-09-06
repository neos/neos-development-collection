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

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Exception\InvalidPositionException;
use Neos\Utility\PositionalArraySorter;
use Neos\Fusion;

/**
 * The old "COA" object
 */
class ArrayImplementation extends AbstractArrayFusionObject
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function evaluate()
    {
        $sortedChildFusionKeys = $this->sortNestedFusionKeys();

        if (count($sortedChildFusionKeys) === 0) {
            return null;
        }

        $output = '';
        foreach ($sortedChildFusionKeys as $key) {
            try {
                $output .= $this->fusionValue($key);
            } catch (\Exception $e) {
                $output .= $this->runtime->handleRenderingException($this->path . '/' . $key, $e);
            }
        }

        return $output;
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
     * @return array an ordered list of keys
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
