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
 * Condition ViewHelper that can evaluate whether the currently authenticated user can access a given Backend module
 *
 * Note: This is a quick fix for https://github.com/neos/neos-development-collection/issues/2854
 * that will be obsolete once the whole Backend module logic is rewritten
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\Backend\IfModuleAccessibleViewHelper
 */
class IfModuleAccessibleViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\Backend\IfModuleAccessibleViewHelper
{
}
