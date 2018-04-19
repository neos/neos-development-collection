<?php
namespace Neos\Media\Domain\Model\AssetSource\Neos;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryResultInterface;
use Neos\Flow\Persistence\QueryInterface;

final class NeosAssetProxyQuery implements AssetProxyQueryInterface
{
    /**
     * @var QueryInterface
     */
    private $flowPersistenceQuery;

    /**
     * @var NeosAssetSource
     */
    private $assetSource;

    /**
     * NeosAssetProxyQuery constructor.
     *
     * @param QueryInterface $flowPersistenceQuery
     * @param NeosAssetSource $assetSource
     */
    public function __construct(QueryInterface $flowPersistenceQuery, NeosAssetSource $assetSource)
    {
        $this->flowPersistenceQuery = $flowPersistenceQuery;
        $this->assetSource = $assetSource;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset): void
    {
        $this->flowPersistenceQuery->setOffset($offset);
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->flowPersistenceQuery->getOffset();
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->flowPersistenceQuery->setLimit($limit);
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->flowPersistenceQuery->getLimit();
    }

    /**
     * @return AssetProxyQueryResultInterface
     */
    public function execute(): AssetProxyQueryResultInterface
    {
        return new NeosAssetProxyQueryResult($this->flowPersistenceQuery->execute(), $this->assetSource);
    }

    /**
     * @param string $searchTerm
     */
    public function setSearchTerm(string $searchTerm)
    {
    }

    /**
     * @return string|void
     */
    public function getSearchTerm()
    {
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->flowPersistenceQuery->count();
    }
}
