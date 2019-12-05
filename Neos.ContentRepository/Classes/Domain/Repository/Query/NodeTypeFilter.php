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
use Neos\ContentRepository\Domain\Model\NodeType;

class NodeTypeFilter implements NodeDataFilterInterface
{
    const OPERATORS = ['=', '!='];

    /**
     * @var NodeType
     */
    protected $nodeType;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @param NodeType $nodeType
     * @param string $operator
     */
    public function __construct(NodeType $nodeType, string $operator = '=')
    {
        $this->nodeType = $nodeType;
        if (in_array($operator, self::OPERATORS) === false) {
            throw new \InvalidArgumentException(sprintf('Invalid operator: %s', $operator), 1541606720);
        }
        $this->operator = $operator;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    public function applyFilter(QueryBuilder $queryBuilder): void
    {
        $nodeTypeName = $this->nodeType->getName();
        $_nodeTypeName = 'nodeTypeName_' . md5($nodeTypeName);

        $queryBuilder
            ->andWhere(sprintf('n.nodeType %s :%s', $this->operator, $_nodeTypeName))
            ->setParameter($_nodeTypeName, $nodeTypeName);
    }
}
