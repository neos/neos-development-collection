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
 * ViewHelper to find out if Neos is rendering a preview mode.
 *
 * = Examples =
 *
 * Given we are currently in a preview mode:
 *
 * <code title="Basic usage">
 * <f:if condition="{neos:rendering.inPreviewMode()}">
 *   <f:then>
 *     Shown in preview.
 *   </f:then>
 *   <f:else>
 *     Shown elsewhere (edit mode or not in backend).
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown in preview.
 * </output>
 *
 *
 * Given we are in the preview mode named "desktop"
 *
 * <code title="Advanced usage">
 *
 * <f:if condition="{neos:rendering.inPreviewMode(mode: 'print')}">
 *   <f:then>
 *     Shown just for print preview mode.
 *   </f:then>
 *   <f:else>
 *     Shown in all other cases.
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown in all other cases.
 * </output>
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\Rendering\InPreviewModeViewHelper
 */
class InPreviewModeViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\Rendering\InPreviewModeViewHelper
{
}
