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
 * A privilege to restrict editing of node properties.
 */
class EditNodePropertyPrivilege extends AbstractNodePropertyPrivilege {

	/**
	 * @var array
	 */
	protected $methodNameToPropertyMapping = array(
		'setName' => 'name',
		'setHidden' => 'hidden',
		'setHiddenInIndex' => 'hiddenInIndex',
		'setHiddenBeforeDateTime' => 'hiddenBeforeDateTime',
		'setHiddenAfterDateTime' => 'hiddenAfterDateTime',
		'setAccessRoles' => 'accessRoles',
	);

	/**
	 * @return string
	 */
	protected function buildMethodPrivilegeMatcher() {
		return 'within(TYPO3\TYPO3CR\Domain\Model\NodeInterface) && method(.*->(setProperty|setName|setHidden|setHiddenBeforeDateTime|setHiddenAfterDateTime|setHiddenInIndex|setAccessRoles)())';
	}

}