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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilege;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilegeSubject;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\NodePrivilegeSubject;

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

	/**
	 * @param NodeInterface $node
	 * @return boolean
	 */
	public function isGrantedToEditNode(NodeInterface $node) {
		return $this->privilegeManager->isGranted(EditNodePrivilege::class, new NodePrivilegeSubject($node));
	}

	/**
	 * @param NodeInterface $node
	 * @param NodeType $typeOfNewNode
	 * @return boolean
	 */
	public function isGrantedToCreateNode(NodeInterface $node, NodeType $typeOfNewNode = NULL) {
		return $this->privilegeManager->isGranted(CreateNodePrivilege::class, new CreateNodePrivilegeSubject($node, $typeOfNewNode));
	}

	/**
	 * @param NodeInterface $node
	 * @return array<string> Array of granted node type names
	 */
	public function getDeniedNodeTypeNames(NodeInterface $node) {
		$privilegeSubject = new CreateNodePrivilegeSubject($node);

		$deniedCreationNodeTypes = array();
		$grantedCreationNodeTypes = array();
		$abstainedCreationNodeTypes = array();
		foreach ($this->securityContext->getRoles() as $role) {
			foreach ($role->getPrivilegesByType('TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilege') as $createNodePrivilege) {

				if (!$createNodePrivilege->matchesSubject($privilegeSubject)) {
					continue;
				}

				if ($createNodePrivilege->isGranted()) {
					$grantedCreationNodeTypes = array_merge($grantedCreationNodeTypes, $createNodePrivilege->getCreationNodeTypes());
				} elseif ($createNodePrivilege->isDenied()) {
					$deniedCreationNodeTypes = array_merge($deniedCreationNodeTypes, $createNodePrivilege->getCreationNodeTypes());
				} else {
					$abstainedCreationNodeTypes = array_merge($abstainedCreationNodeTypes, $createNodePrivilege->getCreationNodeTypes());
				}
			}
		}
		$implicitlyDeniedNodeTypes = array_diff($abstainedCreationNodeTypes, $grantedCreationNodeTypes);
		return array_merge($implicitlyDeniedNodeTypes, $deniedCreationNodeTypes);
	}
}