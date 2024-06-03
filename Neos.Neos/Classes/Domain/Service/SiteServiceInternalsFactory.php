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

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<SiteServiceInternals>
 */
class SiteServiceInternalsFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): SiteServiceInternals
    {
        return new SiteServiceInternals(
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->interDimensionalVariationGraph,
            $serviceFactoryDependencies->nodeTypeManager,
        );
    }
}
