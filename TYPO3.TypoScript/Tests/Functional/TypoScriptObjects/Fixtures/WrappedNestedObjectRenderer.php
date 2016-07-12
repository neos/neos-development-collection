<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects\Fixtures;

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
