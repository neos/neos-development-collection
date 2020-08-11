<?php
namespace Neos\ContentRepository\Security\Authorization\Privilege\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * A privilege to restrict editing of node properties.
 */
class EditNodePropertyPrivilege extends AbstractNodePropertyPrivilege
{
    /**
     * @var array
     */
    protected $methodNameToPropertyMapping = [
        'setName' => 'name',
        'setHidden' => 'hidden',
        'setHiddenInIndex' => 'hiddenInIndex',
        'setHiddenBeforeDateTime' => 'hiddenBeforeDateTime',
        'setHiddenAfterDateTime' => 'hiddenAfterDateTime',
        'setAccessRoles' => 'accessRoles',
    ];

    /**
     * @return string
     */
    protected function buildMethodPrivilegeMatcher()
    {
        return 'within(' . NodeInterface::class . ') && method(.*->(setProperty|setName|setHidden|setHiddenBeforeDateTime|setHiddenAfterDateTime|setHiddenInIndex|setAccessRoles)())';
    }
}
