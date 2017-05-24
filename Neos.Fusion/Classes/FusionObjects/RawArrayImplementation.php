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
use Neos\Fusion\Core\Runtime;

/**
 * Evaluate sub objects to an array (instead of a string as ArrayImplementation does)
 */
class RawArrayImplementation extends ArrayImplementation
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
            return array();
        }

        $output = array();
        foreach ($sortedChildFusionKeys as $key) {
            $value = $this->fusionValue($key);
            if ($value === null && $this->runtime->getLastEvaluationStatus() === Runtime::EVALUATION_SKIPPED) {
                continue;
            }
            $output[$key] = $value;
        }

        return $output;
    }
}
