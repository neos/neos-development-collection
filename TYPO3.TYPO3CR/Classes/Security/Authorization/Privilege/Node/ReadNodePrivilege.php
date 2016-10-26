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

use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilege;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\Doctrine\ConditionGenerator;

/**
 * A node privilege to restricting reading of nodes.
 * Nodes not granted for reading will be filtered via SQL.
 *
 * Currently only doctrine persistence is supported as we use
 * the doctrine filter api, to rewrite SQL queries.
 */
class ReadNodePrivilege extends EntityPrivilege
{
    /**
     * @param string $entityType
     * @return boolean
     */
    public function matchesEntityType($entityType)
    {
        return $entityType === NodeData::class;
    }

    /**
     * @return ConditionGenerator
     */
    protected function getConditionGenerator()
    {
        return new ConditionGenerator();
    }
}
