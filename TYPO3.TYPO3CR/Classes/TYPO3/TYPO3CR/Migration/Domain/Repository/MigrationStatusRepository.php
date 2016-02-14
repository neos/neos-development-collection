<?php
namespace TYPO3\TYPO3CR\Migration\Domain\Repository;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\Repository;

/**
 * Repository for MigrationStatus instances.
 *
 * @Flow\Scope("singleton")
 */
class MigrationStatusRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'version' => QueryInterface::ORDER_ASCENDING
    );
}
