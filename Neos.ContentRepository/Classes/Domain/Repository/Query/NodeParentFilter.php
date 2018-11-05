<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\Repository\Query;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use Doctrine\ORM\QueryBuilder;
use Neos\ContentRepository\Domain\Model\NodeData;

class NodeParentFilter implements NodeDataFilterInterface
{
    /**
     * @var NodeData
     */
    protected $parentNode;

    /**
     * @var bool
     */
    protected $recursive;

    /**
     * NodeParentFilter constructor.
     * @param NodeData $parentNode
     * @param bool $recursive
     */
    public function __construct(NodeData $parentNode, $recursive = false)
    {
        $this->parentNode = $parentNode;
        $this->recursive = $recursive;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function applyFilter(QueryBuilder $queryBuilder): void
    {
        $parentPath = rtrim($this->parentNode->getPath(), '/');
        $parentPathHash = md5($parentPath);
        $_parentPathHash = 'parentPathHash_' . md5($parentPathHash);

        if ($this->recursive === true) {
            $parentPathRecursive = $parentPath . '/%';
            $_parentPathRecursive = 'parentPathRecursive_' . md5($parentPathRecursive);

            $queryBuilder
                ->andWhere(sprintf('(n.parentPathHash = :%s OR n.parentPath LIKE :%s)', $_parentPathHash, $_parentPathRecursive))
                ->setParameter($_parentPathHash, $parentPathHash)
                ->setParameter($_parentPathRecursive, $parentPathRecursive);
        } else {
            $queryBuilder
                ->andWhere(sprintf('n.parentPathHash = :%s', $_parentPathHash))
                ->setParameter($_parentPathHash, $parentPathHash);
        }
    }
}
