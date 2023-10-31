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

namespace Neos\Neos\ViewHelpers\Backend;

/**
 * Render HTML markup for the full configuration tree in the Neos Administration -> Configuration Module.
 *
 * For performance reasons, this is done inside a ViewHelper instead of Fluid itself.
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\Backend\ConfigurationTreeViewHelper
 */
class ConfigurationTreeViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\Backend\ConfigurationTreeViewHelper
{
}
