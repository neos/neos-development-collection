<?php
namespace Neos\EventSourcedContentRepository\Security\Authorization\Privilege\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\DisableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\EnableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeReferences;

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
        return  'method(' . SetNodeProperties::class . '->__construct()) || method(' . SetNodeReferences::class . '->__construct()) || method(' . EnableNodeAggregate::class . '->__construct()) || method(' . DisableNodeAggregate::class . '->__construct()) || method(' . ChangeNodeAggregateName::class . '->__construct()) || method(' . ChangeNodeAggregateType::class . '->__construct())';
    }
}
