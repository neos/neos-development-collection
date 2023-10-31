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

namespace Neos\Neos\ViewHelpers\Uri;

use Neos\Flow\Annotations as Flow;

/**
 * A view helper for creating links to modules.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <link rel="some-module" href="{neos:uri.module(path: 'system/useradmin')}" />
 * </code>
 *
 * <output>
 * <link rel="some-module" href="neos/system/useradmin" />
 * </output>
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\Uri\ModuleViewHelper
 * @Flow\Scope("prototype")
 */
class ModuleViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\Uri\ModuleViewHelper
{
}
