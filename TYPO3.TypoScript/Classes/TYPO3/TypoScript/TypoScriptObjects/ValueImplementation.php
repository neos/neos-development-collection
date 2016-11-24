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

use Neos\Flow\Annotations as Flow;

/**
 * Value object for simple type handling as TypoScript objects
 *
 * //tsPath value The value this object should be evaluated to
 * @api
 */
class ValueImplementation extends AbstractTypoScriptObject
{
    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->tsValue('value');
    }

    /**
     * Just return the processed value
     *
     * @return mixed
     */
    public function evaluate()
    {
        return $this->getValue();
    }
}
