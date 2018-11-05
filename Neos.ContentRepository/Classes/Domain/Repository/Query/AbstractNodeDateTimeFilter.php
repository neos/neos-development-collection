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

namespace Neos\ContentRepository\Domain\Repository\Query;

use DateTime;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractNodeDateTimeFilter implements NodeDataFilterInterface
{
    const OPERATORS = ['=', '!=', '>', '>=', '<=', '<'];

    /**
     * @var DateTime
     */
    protected $value;

    /**
     * @var string
     */
    protected $operator;

    /**
     * NodeLastPublicationDateTimeFilter constructor.
     * @param DateTime $value
     * @param string $operator
     */
    public function __construct(DateTime $value, $operator = '=')
    {
        $this->value = $value;
        if (in_array($operator, self::OPERATORS) === false) {
            throw new \InvalidArgumentException(sprintf('Invalid operator: %s', $operator), 1541608194);
        }
        $this->operator = $operator;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function applyFilter(QueryBuilder $queryBuilder): void
    {
        $_value = 'value_' . md5($this->value->format(DateTime::ATOM));

        $queryBuilder
            ->andWhere(sprintf('n.%s %s :%s', $this->getFieldName(), $this->operator, $_value))
            ->setParameter($_value, $this->value);
    }

    abstract protected function getFieldName(): string;
}
