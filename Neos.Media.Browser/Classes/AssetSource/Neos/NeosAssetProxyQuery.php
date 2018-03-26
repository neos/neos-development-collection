<?php
namespace Neos\Media\Browser\AssetSource\Neos;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 * (c) Robert Lemke, Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Browser\AssetSource\AssetProxyQuery;
use Neos\Media\Browser\AssetSource\AssetProxyQueryResult;
use Neos\Flow\Persistence\QueryInterface;

final class NeosAssetProxyQuery implements AssetProxyQuery
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
     * @return AssetProxyQueryResult
     */
    public function execute(): AssetProxyQueryResult
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
