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
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

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

    /**
     * Find the matching site for a given Node
     *
     * @param string $nodePath
     * @return Site
     */
    public function findOneByNodePath($nodePath)
    {
        if (!NodePaths::isSubPathOf('/sites', $nodePath)) {
            return null;
        }

        $nodePathParts =explode('/', $nodePath);
        if (!isset($nodePathParts[2])) {
            return null;
        }

        $siteNodeName = $nodePathParts[2];
        return $this->findOneByNodeName($siteNodeName);
    }
}
