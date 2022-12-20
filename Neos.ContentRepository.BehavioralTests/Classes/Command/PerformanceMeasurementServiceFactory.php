<?php

/*
 * This file is part of the Neos.ContentRepository.BehavioralTests package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Command;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<PerformanceMeasurementService>
 * @internal
 */
class PerformanceMeasurementServiceFactory implements ContentRepositoryServiceFactoryInterface
{

    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): PerformanceMeasurementService
    {
        return new PerformanceMeasurementService(
            $serviceFactoryDependencies->eventPersister,
            $serviceFactoryDependencies->contentRepository,
            $this->connection,
            $serviceFactoryDependencies->contentRepositoryId
        );
    }
}
