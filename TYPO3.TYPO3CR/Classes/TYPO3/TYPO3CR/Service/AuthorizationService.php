<?php
namespace TYPO3\TYPO3CR\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

/**
 * This service provides API methods to check for privileges
 * on nodes and permissions for node actions.
 *
 * @Flow\Scope("singleton")
 */
class AuthorizationService {

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;
}