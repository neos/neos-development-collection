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
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'mixed', 'The value to get the type of');
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return gettype($this->arguments['value'] ?: $this->renderChildren());
    }
}
