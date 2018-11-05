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

class NodePathOrder extends AbstractNodeDataOrder
{
    /**
     * @param QueryBuilder $queryBuilder
     */
    public function applyOrder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->orderBy('n.path', $this->order);
    }
}
