<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;

/**
 * @deprecated TODO CLEAN UP
 */
final class AutoUriPathResolverFactoryInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        public readonly ContentDimensionSourceInterface $contentDimensionSource
    ) {
    }
}
