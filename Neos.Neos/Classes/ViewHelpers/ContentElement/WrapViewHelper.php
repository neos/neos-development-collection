<?php

declare(strict_types=1);

namespace Neos\Neos\ViewHelpers\ContentElement;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A view helper for manually wrapping content editables.
 *
 * Note that using this view helper is usually not necessary as Neos will automatically wrap editables of content
 * elements.
 *
 * By explicitly wrapping template parts with node meta data that is required for the backend to show properties in the
 * inspector, this ViewHelper enables usage of the ``contentElement.editable`` ViewHelper outside of content element
 * templates. This is useful if you want to make properties of a custom document node inline-editable.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <neos:contentElement.wrap>
 *  <div>{neos:contentElement.editable(property: 'someProperty')}</div>
 * </neos:contentElement.wrap>
 * </code>
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\ContentElement\WrapViewHelper
 */
class WrapViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\ContentElement\WrapViewHelper
{
}
