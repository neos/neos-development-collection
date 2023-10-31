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

namespace Neos\Neos\ViewHelpers\Rendering;

/**
 * ViewHelper to find out if Neos is rendering the live website.
 * Make sure you either give a node from the current context to
 * the ViewHelper or have "node" set as template variable at least.
 *
 * = Examples =
 *
 * Given we are currently seeing the Neos backend:
 *
 * <code title="Basic usage">
 * <f:if condition="{neos:rendering.live()}">
 *   <f:then>
 *     Shown outside the backend.
 *   </f:then>
 *   <f:else>
 *     Shown in the backend.
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown in the backend.
 * </output>
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\Rendering\LiveViewHelper
 */
class LiveViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\Rendering\LiveViewHelper
{
}
