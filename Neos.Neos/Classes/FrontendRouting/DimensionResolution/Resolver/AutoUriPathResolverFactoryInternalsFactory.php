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

use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @deprecated TODO CLEAN UP
 * @implements ContentRepositoryServiceFactoryInterface<AutoUriPathResolverFactoryInternals>
 */
final class AutoUriPathResolverFactoryInternalsFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): AutoUriPathResolverFactoryInternals {
        return new AutoUriPathResolverFactoryInternals(
            $serviceFactoryDependencies->contentDimensionSource
        );
    }
}
