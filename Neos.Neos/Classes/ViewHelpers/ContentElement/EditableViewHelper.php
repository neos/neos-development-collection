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

namespace Neos\Neos\ViewHelpers\ContentElement;

/**
 * Renders a wrapper around the inner contents of the tag to enable frontend editing.
 *
 * The wrapper contains the property name which should be made editable, and is by default
 * a "div" tag. The tag to use can be given as `tag` argument to the ViewHelper.
 *
 * In live workspace this just renders a tag with the specified $tag-name containing the value of the given $property.
 * For logged in users with access to the Backend this also adds required attributes for the RTE to work.
 *
 * Note: when passing a node you have to make sure a metadata wrapper is used around this that matches the given node
 * (see contentElement.wrap - i.e. the WrapViewHelper).
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\ContentElement\EditableViewHelper
 */
class EditableViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\ContentElement\EditableViewHelper
{
}
