<?php
namespace TYPO3\Neos\Security\Authorization\Privilege;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege;

/**
 * A privilege to show (document) nodes in the navigate component of the Neos backend. This also includes any manipulation of the affected nodes
 */
class NodeTreePrivilege extends EditNodePrivilege {

}