<?php
namespace Neos\Media\Domain\Model\AssetSource;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

interface AssetProxyQueryInterface
{
    /**
     * @param int $offset
     */
    public function setOffset(int $offset): void;

    /**
     * @return int
     */
    public function getOffset(): int;

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void;

    /**
     * @return int
     */
    public function getLimit(): int;

    /**
     * @param string $searchTerm
     */
    public function setSearchTerm(string $searchTerm);

    /**
     * @return string
     */
    public function getSearchTerm();

    /**
     * @return AssetProxyQueryResultInterface
     * @throws AssetSourceConnectionExceptionInterface
     */
    public function execute(): AssetProxyQueryResultInterface;

    /**
     * @return int
     */
    public function count(): int;
}
