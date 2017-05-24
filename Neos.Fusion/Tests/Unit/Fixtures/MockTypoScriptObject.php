<?php
namespace Neos\Fusion;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A mock for a Fusion object
 *
 */
class MockFusionObject
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
