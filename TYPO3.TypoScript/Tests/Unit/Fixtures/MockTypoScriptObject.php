<?php
namespace TYPO3\TypoScript;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A mock for a TypoScript object
 *
 */
class MockTypoScriptObject
{
    protected $value;

    /**
     * Enter description here...
     *
     * @param unknown_type $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}
