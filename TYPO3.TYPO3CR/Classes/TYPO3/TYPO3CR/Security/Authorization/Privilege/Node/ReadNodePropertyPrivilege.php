<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A privilege to restrict reading of node properties.
 *
 * This is needed, as the technical implementation is not based on the entity privilege type, that
 * the read node privilege (retrieving the node at all) ist based on.
 */
class ReadNodePropertyPrivilege extends AbstractNodePropertyPrivilege {

	/**
	 * @var array
	 */
	protected $methodNameToPropertyMapping = array(
		'getName' => 'name',
		'isHidden' => 'hidden',
		'isHiddenInIndex' => 'hiddenInIndex',
		'getHiddenBeforeDateTime' => 'hiddenBeforeDateTime',
		'getHiddenAfterDateTime' => 'hiddenAfterDateTime',
		'getAccessRoles' => 'accessRoles',
	);

	/**
	 * @return string
	 */
	protected function buildMethodPrivilegeMatcher() {
		return 'within(TYPO3\TYPO3CR\Domain\Model\NodeInterface) && method(.*->(getProperty|getName|isHidden|getHiddenBeforeDateTime|getHiddenAfterDateTime|isHiddenInIndex|getAccessRoles)())';
	}
}