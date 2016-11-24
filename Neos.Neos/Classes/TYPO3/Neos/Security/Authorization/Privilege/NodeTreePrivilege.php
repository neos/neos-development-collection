<?php
namespace Neos\Neos\Security\Authorization\Privilege;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege;

/**
 * A privilege to show (document) nodes in the navigate component of the Neos backend. This also includes any manipulation of the affected nodes
 */
class NodeTreePrivilege extends EditNodePrivilege
{
}
