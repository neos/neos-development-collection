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

class NodeIdentifierFilter implements NodeDataFilterInterface
{
    /**
     * @var string
     */
    protected $nodeIdentifier;

    /**
     * NodeIdentifierFilter constructor.
     * @param string $nodeIdentifier
     */
    public function __construct(string $nodeIdentifier)
    {
        $this->nodeIdentifier = $nodeIdentifier;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function applyFilter(QueryBuilder $queryBuilder): void
    {
        $_identifier = 'identifier_' . md5($this->nodeIdentifier);

        $queryBuilder
            ->andWhere(sprintf('n.identifier = :%s', $_identifier))
            ->setParameter($_identifier, $this->nodeIdentifier);
    }
}
