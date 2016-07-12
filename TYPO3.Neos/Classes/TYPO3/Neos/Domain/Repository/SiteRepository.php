<?php
namespace TYPO3\Neos\Domain\Repository;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * The Site Repository
 *
 * @Flow\Scope("singleton")
 * @api
 */
class SiteRepository extends \TYPO3\Flow\Persistence\Repository
{
    /**
     * Finds the first site
     *
     * @return \TYPO3\Neos\Domain\Model\Site The first site or NULL if none exists
     * @api
     */
    public function findFirst()
    {
        return $this->createQuery()->execute()->getFirst();
    }

    /**
     * Find all sites with status "online"
     *
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
    public function findOnline()
    {
        return $this->findByState(\TYPO3\Neos\Domain\Model\Site::STATE_ONLINE);
    }

    /**
     * Find first site with status "online"
     *
     * @return \TYPO3\Neos\Domain\Model\Site
     */
    public function findFirstOnline()
    {
        return $this->findOnline()->getFirst();
    }
}
