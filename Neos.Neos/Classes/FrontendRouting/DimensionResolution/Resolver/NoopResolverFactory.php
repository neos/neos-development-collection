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

use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
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
    /**
     * @param array<string,mixed> $dimensionResolverOptions
     */
    public function create(
        ContentRepositoryIdentifier $contentRepositoryIdentifier,
        array $dimensionResolverOptions
    ): DimensionResolverInterface {
        return new NoopResolver();
    }
}
