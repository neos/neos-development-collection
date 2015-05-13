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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A node privilege subject which can restricted to a single node property
 */
class PropertyAwareNodePrivilegeSubject extends NodePrivilegeSubject {

	/**
	 * @var NodeInterface
	 */
	protected $node;

	/**
	 * @var string
	 */
	protected $propertyName = NULL;

	/**
	 * @var JoinPointInterface
	 */
	protected $joinPoint = NULL;

	/**
	 * @param NodeInterface $node
	 * @param JoinPointInterface $joinPoint
	 * @param string $propertyName
	 */
	public function __construct(NodeInterface $node, JoinPointInterface $joinPoint = NULL, $propertyName = NULL) {
		$this->propertyName = $propertyName;
		parent::__construct($node, $joinPoint);
	}

	/**
	 * @return string
	 */
	public function getPropertyName() {
		return $this->propertyName;
	}

	/**
	 * @return boolean
	 */
	public function hasPropertyName() {
		return $this->propertyName !== NULL;
	}
}