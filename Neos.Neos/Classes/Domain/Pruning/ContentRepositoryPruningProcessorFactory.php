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

namespace Neos\Neos\Domain\Pruning;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ContentRepositoryPruningProcessor>
 */
final readonly class ContentRepositoryPruningProcessorFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct()
    {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        return new ContentRepositoryPruningProcessor(
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->eventStore,
        );
    }
}
