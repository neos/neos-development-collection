<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\TypoScriptObjects\AbstractArrayTypoScriptObject;

/**
 * Renderer which wraps the nested TS object found at "value" with "prepend" and "append".
 *
 * Needed for more complex prototype inheritance chain testing.
 */
class WrappedNestedObjectRenderer extends AbstractArrayTypoScriptObject
{
    /**
     * The string the current value should be prepended with
     *
     * @return string
     */
    public function getPrepend()
    {
        return $this->tsValue('prepend');
    }

    /**
     * The string the current value should be suffixed with
     *
     * @return string
     */
    public function getAppend()
    {
        return $this->tsValue('append');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function evaluate()
    {
        return $this->getPrepend() . $this->tsRuntime->evaluate($this->path . '/value') . $this->getAppend();
    }
}
