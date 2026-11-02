<?php

declare(strict_types=1);

namespace Neos\Media\Tests\Functional\Fixtures\ThumbnailGenerator;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Model\ThumbnailGenerator\EagerThumbnailGeneratorInterface;

/**
 * A testing generator that claims thumbnail generation for specifically titled assets
 * but never renders anything itself, so the regular generator chain stays in charge.
 */
class EagerTestingThumbnailGenerator implements EagerThumbnailGeneratorInterface
{
    public const CLAIMED_ASSET_TITLE = 'eager-thumbnail-generator-claim';

    public static function getPriority()
    {
        return 0;
    }

    public function canRefresh(Thumbnail $thumbnail)
    {
        return false;
    }

    public function refresh(Thumbnail $thumbnail)
    {
    }

    public function claimsThumbnailGeneration(AssetInterface $asset, ThumbnailConfiguration $configuration): bool
    {
        return $asset->getTitle() === self::CLAIMED_ASSET_TITLE;
    }
}
