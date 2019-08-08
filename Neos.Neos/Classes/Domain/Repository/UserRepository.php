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
    /**
     * @return QueryResultInterface
     */
    public function findAllOrderedByUsername(): QueryResultInterface
    {
        return $this->createQuery()
            ->setOrderings(['accounts.accountIdentifier' => QueryInterface::ORDER_ASCENDING])
            ->execute();
    }

    /**
     * @param string $searchTerm
     * @return QueryResultInterface
     */
    public function findBySearchTerm(string $searchTerm): QueryResultInterface
    {
        try {
            $query = $this->createQuery();
            $query->matching(
                $query->logicalOr(
                    $query->like('accounts.accountIdentifier', '%'.$searchTerm.'%'),
                    $query->like('name.fullName', '%'.$searchTerm.'%')
                )
            );
            return $query->setOrderings(['accounts.accountIdentifier' => QueryInterface::ORDER_ASCENDING])->execute();
        } catch (\Neos\Flow\Persistence\Exception\InvalidQueryException $e) {
            throw new \RuntimeException($e->getMessage(), 1557767046, $e);
        }
    }
}
