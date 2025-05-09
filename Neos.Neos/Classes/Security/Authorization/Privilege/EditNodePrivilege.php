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

namespace Neos\Neos\Security\Authorization\Privilege;

/**
 * The privilege to edit any matching node in the Content Repository.
 * This includes creation, setting properties or references, disabling/enabling, tagging and moving corresponding nodes
 */
class EditNodePrivilege extends AbstractSubtreeTagBasedPrivilege
{
}
