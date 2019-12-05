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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\QueryBuilder;

class NodePropertyFilter implements NodeDataFilterInterface
{
    const OPERATORS = ['=', '!=', '>', '>=', '<=', '<'];

    /**
     * @var string
     */
    protected $key;

    protected $value;

    /**
     * @var string
     */
    protected $operator;

    /**
     * NodePropertyFilter constructor.
     * @param string $key
     * @param $value
     * @param string $operator
     */
    public function __construct(string $key, $value, $operator = '=')
    {
        $this->key = $key;
        $this->value = $value;
        if (in_array($operator, self::OPERATORS) === false) {
            throw new \InvalidArgumentException(sprintf('Invalid operator: %s', $operator), 1541606716);
        }
        $this->operator = $operator;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function applyFilter(QueryBuilder $queryBuilder): void
    {
        $dbPlatform = $queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform();

        if ($dbPlatform instanceof PostgreSqlPlatform) {
            $key = $this->key;
        } elseif ($dbPlatform instanceof MySqlPlatform || $dbPlatform instanceof SqlitePlatform) {
            $key = '$.' . $this->key;
        } else {
            throw DBALException::notSupported('JSON_EXTRACT');
        }

        $_key = 'key_' . md5($key);
        $_value = 'value_' . md5((string)$this->value);

        $queryBuilder
            ->andWhere(sprintf('JSON_EXTRACT(n.properties, :%s) %s :%s', $_key, $this->operator, $_value))
            ->setParameter($_key, $key)
            ->setParameter($_value, $this->value);
    }
}
