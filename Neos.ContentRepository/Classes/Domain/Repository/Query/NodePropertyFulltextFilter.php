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

use Neos\Utility\Unicode\Functions as UnicodeFunctions;
use Doctrine\ORM\QueryBuilder;

class NodePropertyFulltextFilter implements NodeDataFilterInterface
{
    /**
     * @var string
     */
    protected $search;

    /**
     * NodePropertyFilter constructor.
     * @param string $search
     */
    public function __construct(string $search)
    {
        $this->search = $search;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function applyFilter(QueryBuilder $queryBuilder): void
    {
        $_search = 'search_' . md5($this->search);

        $search = '%' . trim(json_encode(UnicodeFunctions::strtolower($this->search), JSON_UNESCAPED_UNICODE), '"') . '%';

        $queryBuilder
            ->andWhere(sprintf('LOWER(NEOSCR_TOSTRING(n.properties)) LIKE :%s', $_search))
            ->setParameter($_search, $search);
    }
}
