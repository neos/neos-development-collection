<?php

namespace Neos\Neos\Domain\Repository;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Repository;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;

/**
 * The User Repository
 *
 * @Flow\Scope("singleton")
 * @api
 */
class UserRepository extends Repository
{
    const SORT_BY_LASTLOGGEDIN = 'LastLoggedIn';
    const SORT_DIRECTION_DESC = 'DESC';

    /**
     * @return QueryResultInterface
     * @deprecated
     */
    public function findAllOrderedByUsername(): QueryResultInterface
    {
        return $this->findAllOrdered('accountIdentifier');
    }

    public function findAllOrdered(string $fieldName, string $sortDirection = QueryInterface::ORDER_ASCENDING): QueryResultInterface
    {
        $allowedFieldNames = ['accounts.accountIdentifier', 'accounts.lastSuccessfulAuthenticationDate', 'name.fullName'];

        if (!in_array($fieldName, $allowedFieldNames)) {
            throw new \InvalidArgumentException(sprintf('The field name "%s" is invalid, must be one of %s', $fieldName, implode(',', $allowedFieldNames)), 1651580413);
        }

        return $this->createQuery()
            ->setOrderings([$fieldName => $sortDirection])
            ->execute();
    }

    public function findBySearchTerm(string $searchTerm, string $sortBy, string $sortDirection): QueryResultInterface
    {
        try {
            $query = $this->createQuery();
            $query->matching(
                $query->logicalOr(
                    $query->like('accounts.accountIdentifier', '%' . $searchTerm . '%'),
                    $query->like('name.fullName', '%' . $searchTerm . '%')
                )
            );
            return $query->setOrderings([$sortBy => $sortDirection])->execute();
        } catch (\Neos\Flow\Persistence\Exception\InvalidQueryException $e) {
            throw new \RuntimeException($e->getMessage(), 1557767046, $e);
        }
    }
}
