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

namespace Neos\Neos\Controller\Service;

use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @deprecated
 * @implements ContentRepositoryServiceFactoryInterface<ContentDimensionsControllerInternals>
 */
class ContentDimensionsControllerInternalsFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): ContentDimensionsControllerInternals {
        return new ContentDimensionsControllerInternals(
            $serviceFactoryDependencies->contentDimensionZookeeper
        );
    }
}
