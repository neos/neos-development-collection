<?php
namespace TYPO3\Media\Domain\Repository;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A repository for Tags
 *
 * @Flow\Scope("singleton")
 */
class TagRepository extends \TYPO3\Flow\Persistence\Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array('label' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING);

    /**
     * @param string $searchTerm
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
    public function findBySearchTerm($searchTerm)
    {
        $query = $this->createQuery();
        return $query->matching($query->like('label', '%' . $searchTerm . '%'))->execute();
    }
}
