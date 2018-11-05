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

class NodeNameFilter implements NodeDataFilterInterface
{
    /**
     * @var string
     */
    protected $nodeName;

    /**
     * @param string $nodeName
     */
    public function __construct(string $nodeName)
    {
        $this->nodeName = $nodeName;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function applyFilter(QueryBuilder $queryBuilder): void
    {
        $_nodeName = 'nodeName_' . md5($this->nodeName);

        $queryBuilder
            ->andWhere(sprintf('n.path LIKE :%s', $_nodeName))
            ->setParameter($_nodeName, '%/' . $this->nodeName);
    }
}
