<?php
namespace TYPO3\Neos\ViewHelpers;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * View helper to check if a given value is an array.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * {neos:getType(value: 'foo')}
 * </code>
 * <output>
 * string
 * </output>
 *
 * <code title="Use with shorthand syntax">
 * {myValue -> neos:getType()}
 * </code>
 * <output>
 * string
 * (if myValue is a string)
 * </output>
 */
class GetTypeViewHelper extends AbstractViewHelper
{
    /**
     * @param mixed $value The value to determine the type of
     * @return string
     */
    public function render($value = null)
    {
        return gettype($value ?: $this->renderChildren());
    }
}
