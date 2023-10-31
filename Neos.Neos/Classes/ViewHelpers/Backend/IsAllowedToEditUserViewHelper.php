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
 * Returns true, if the current user is allowed to edit the given user, false otherwise.
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\Backend\IsAllowedToEditUserViewHelper
 */
class IsAllowedToEditUserViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\Backend\IsAllowedToEditUserViewHelper
{
}
