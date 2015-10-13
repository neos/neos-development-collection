<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A privilege to restrict editing of node properties.
 */
class EditNodePropertyPrivilege extends AbstractNodePropertyPrivilege
{
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
    protected function buildMethodPrivilegeMatcher()
    {
        return 'within(TYPO3\TYPO3CR\Domain\Model\NodeInterface) && method(.*->(setProperty|setName|setHidden|setHiddenBeforeDateTime|setHiddenAfterDateTime|setHiddenInIndex|setAccessRoles)())';
    }
}
