<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\ViewHelpers;

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
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\GetTypeViewHelper
 */
class GetTypeViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\GetTypeViewHelper
{
}
