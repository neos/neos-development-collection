<?php
namespace Neos\Media\Domain\Model\ThumbnailGenerator;

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
use Neos\Media\Domain\Model\ThumbnailConfiguration;

/**
 * A ThumbnailGenerator that wants to process assets even when the requested thumbnail
 * would be identical to the original (same dimensions, no quality or format change).
 *
 * By default the ThumbnailService returns the original asset in that case, so no
 * generator ever runs. Generators that do more than resizing — watermarking, image
 * optimisation, provenance markings — implement this interface to claim such assets:
 * if any registered generator claims an asset, the shortcut is skipped and a thumbnail
 * is created and rendered through the generator chain as usual.
 *
 * Implementations should keep claimsThumbnailGeneration() cheap: it runs for every
 * thumbnail request that would otherwise take the shortcut.
 */
interface EagerThumbnailGeneratorInterface extends ThumbnailGeneratorInterface
{
    /**
     * Whether this generator wants a thumbnail to be generated for the given asset and
     * configuration although the rendered result would otherwise be the unmodified original.
     *
     * @param AssetInterface $asset The original asset a thumbnail was requested for
     * @param ThumbnailConfiguration $configuration The requested thumbnail configuration
     * @return boolean true if a thumbnail should be generated for this asset
     * @api
     */
    public function claimsThumbnailGeneration(AssetInterface $asset, ThumbnailConfiguration $configuration): bool;
}
