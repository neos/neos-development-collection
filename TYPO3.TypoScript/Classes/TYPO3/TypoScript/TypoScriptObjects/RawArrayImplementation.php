<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\Core\Runtime;

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
        $sortedChildTypoScriptKeys = $this->sortNestedTypoScriptKeys();

        if (count($sortedChildTypoScriptKeys) === 0) {
            return array();
        }

        $output = array();
        foreach ($sortedChildTypoScriptKeys as $key) {
            $value = $this->tsValue($key);
            if ($value === null && $this->tsRuntime->getLastEvaluationStatus() === Runtime::EVALUATION_SKIPPED) {
                continue;
            }
            $output[$key] = $value;
        }

        return $output;
    }
}
