<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * A create node privilege subject
 */
class CreateNodePrivilegeSubject extends NodePrivilegeSubject {

	/**
	 * @var NodeType
	 */
	protected $creationNodeType;

	/**
	 * @param NodeInterface $node The parent node under which a new child shall be created
	 * @param NodeType $creationNodeType The node type of the new child node, to check if this is type is allowed as new child node under the given parent node
	 * @param JoinPointInterface $joinPoint Set, if created by a method interception. Usually the interception of the createNode() method, where the creation of new child nodes takes place
	 */
	public function __construct(NodeInterface $node, NodeType $creationNodeType = NULL, JoinPointInterface $joinPoint = NULL) {
		$this->creationNodeType = $creationNodeType;
		parent::__construct($node, $joinPoint);
	}

	/**
	 * @return boolean
	 */
	public function hasCreationNodeType() {
		return ($this->creationNodeType !== NULL);
	}

	/**
	 * @return NodeType
	 */
	public function getCreationNodeType() {
		return $this->creationNodeType;
	}
}