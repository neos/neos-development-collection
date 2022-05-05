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

use Neos\ContentRepository\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Feature\NodeModification\Command\SetSerializedNodeProperties;

/**
 * A privilege to restrict editing of node properties.
 */
class EditNodePropertyPrivilege extends AbstractNodePropertyPrivilege
{
    /**
     * @var array<string,string>
     */
    protected array $methodNameToPropertyMapping = [
        'setName' => 'name',
        'setHidden' => 'hidden',
        'setHiddenInIndex' => 'hiddenInIndex',
        'setHiddenBeforeDateTime' => 'hiddenBeforeDateTime',
        'setHiddenAfterDateTime' => 'hiddenAfterDateTime',
        'setAccessRoles' => 'accessRoles',
    ];

    protected function buildMethodPrivilegeMatcher(): string
    {
        return  'method(' . SetSerializedNodeProperties::class . '->__construct()) || method('
            . SetNodeReferences::class . '->__construct()) || method('
            . EnableNodeAggregate::class . '->__construct()) || method('
            . DisableNodeAggregate::class . '->__construct()) || method('
            . ChangeNodeAggregateName::class . '->__construct()) || method('
            . ChangeNodeAggregateType::class . '->__construct())';
    }
}
