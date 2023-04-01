<?php

declare(strict_types=1);

namespace Neos\Media\Eel;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;

/**
 * This is a helper for accessing assets from the media library
 *
 * @api
 */
class AssetsHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @return QueryResultInterface<AssetInterface> | null
     */
    public function findByTag(Tag|string $tag): ?QueryResultInterface
    {
        if (is_string($tag)) {
            $tag = $this->tagRepository->findOneByLabel($tag);
        }

        if (!$tag) {
            return null;
        }

        try {
            return $this->assetRepository->findByTag($tag);
        } catch (InvalidQueryException) {
        }

        return null;
    }

    /**
     * @return QueryResultInterface<AssetInterface> | null
     */
    public function findByCollection(AssetCollection|string $collection): ?QueryResultInterface
    {
        if (is_string($collection)) {
            $collection = $this->assetCollectionRepository->findOneByTitle($collection);
        }

        if (!$collection) {
            return null;
        }

        try {
            return $this->assetRepository->findByAssetCollection($collection);
        } catch (InvalidQueryException) {
        }

        return null;
    }

    /**
     * @param Tag[] $tags
     * @return QueryResultInterface<AssetInterface> | null
     */
    public function findBySearchTerm(string $searchTerm, array $tags = [], AssetCollection|string $collection = null): ?QueryResultInterface
    {
        if (is_string($collection)) {
            $collection = $this->assetCollectionRepository->findOneByTitle($collection);
        }

        if (!$searchTerm) {
            return null;
        }

        try {
            return $this->assetRepository->findBySearchTermOrTags($searchTerm, $tags, $collection);
        } catch (InvalidQueryException) {
        }

        return null;
    }

    /**
     * @param string $methodName
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
