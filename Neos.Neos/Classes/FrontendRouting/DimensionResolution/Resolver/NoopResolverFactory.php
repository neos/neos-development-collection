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

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Neos\Domain\Model\SiteConfiguration;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverFactoryInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;

/**
 * Resolver which does not do anything.
 *
 * See {@see DimensionResolverInterface} for detailed documentation.
 *
 * @api
 */
final class NoopResolverFactory implements DimensionResolverFactoryInterface
{
    public function create(
        ContentRepositoryId $contentRepositoryId,
        SiteConfiguration $siteConfiguration,
    ): DimensionResolverInterface {
        return new NoopResolver();
    }
}
