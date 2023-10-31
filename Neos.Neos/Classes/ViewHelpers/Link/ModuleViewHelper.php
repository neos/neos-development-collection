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

namespace Neos\Neos\ViewHelpers\Link;

/**
 * A view helper for creating links to modules.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <neos:link.module path="system/useradmin">some link</neos:link.module>
 * </code>
 * <output>
 * <a href="neos/system/useradmin">some link</a>
 * </output>
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\Link\ModuleViewHelper
 */
class ModuleViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\Link\ModuleViewHelper
{
}
