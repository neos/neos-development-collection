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

use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryResultInterface;
use Neos\Flow\Annotations\Proxy;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Media\Domain\Model\AssetInterface;

/**
 * @Proxy(false)
 */
final class NeosAssetProxyQueryResult implements AssetProxyQueryResultInterface
{
    /**
     * @var NeosAssetSource
     */
    private $assetSource;

    /**
     * @var QueryResultInterface
     */
    private $flowPersistenceQueryResult;

    /**
     * @var NeosAssetProxyQuery
     */
    private $query;

    /**
     * @param QueryResultInterface $flowPersistenceQueryResult
     * @param NeosAssetSource $assetSource
     */
    public function __construct(QueryResultInterface $flowPersistenceQueryResult, NeosAssetSource $assetSource)
    {
        $this->flowPersistenceQueryResult = $flowPersistenceQueryResult;
        $this->assetSource = $assetSource;
    }

    /**
     * @return AssetProxyQueryInterface
     */
    public function getQuery(): AssetProxyQueryInterface
    {
        if ($this->query === null) {
            $this->query = new NeosAssetProxyQuery($this->flowPersistenceQueryResult->getQuery(), $this->assetSource);
        }
        return $this->query;
    }

    /**
     * @return AssetProxyInterface|null
     */
    public function getFirst(): ?AssetProxyInterface
    {
        $asset = $this->flowPersistenceQueryResult->getFirst();
        if ($asset instanceof AssetInterface) {
            return new NeosAssetProxy($asset, $this->assetSource);
        } else {
            return null;
        }
    }

    /**
     * @return NeosAssetProxy[]
     */
    public function toArray(): array
    {
        $assetProxies = [];
        foreach ($this->flowPersistenceQueryResult->toArray() as $asset) {
            $assetProxies[] = new NeosAssetProxy($asset, $this->assetSource);
        }
        return $assetProxies;
    }

    /**
     * @return AssetProxyInterface|null
     */
    public function current(): ?AssetProxyInterface
    {
        $asset = $this->flowPersistenceQueryResult->current();
        if ($asset instanceof AssetInterface) {
            return new NeosAssetProxy($asset, $this->assetSource);
        } else {
            return null;
        }
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->flowPersistenceQueryResult->next();
    }

    /**
     * @return AssetProxyInterface|null
     */
    public function key()
    {
        return $this->flowPersistenceQueryResult->key();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->flowPersistenceQueryResult->valid();
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->flowPersistenceQueryResult->rewind();
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->flowPersistenceQueryResult->offsetExists($offset);
    }

    /**
     * @param mixed $offset
     * @return AssetProxyInterface|mixed
     */
    public function offsetGet($offset): ?AssetProxyInterface
    {
        return new NeosAssetProxy($this->flowPersistenceQueryResult->offsetGet($offset), $this->assetSource);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Unsupported operation: ' . __METHOD__, 1510060444);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Unsupported operation: ' . __METHOD__, 1510060467);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->flowPersistenceQueryResult->count();
    }
}
