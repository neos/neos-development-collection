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
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Processors\EventExportProcessor;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Import\LiveWorkspaceIsEmptyProcessor;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * @implements ContentRepositoryServiceFactoryInterface<LiveWorkspaceIsEmptyProcessor>
 */
final readonly class SitePruningProcessorFactory implements ContentRepositoryServiceFactoryInterface
{

    public function __construct(
        private WorkspaceName $workspaceName,
        private SiteRepository $siteRepository,
        private DomainRepository $domainRepository,
        private PersistenceManagerInterface $persistenceManager,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        return new SitePruningProcessor(
            $serviceFactoryDependencies->contentRepository,
            $this->workspaceName,
            $this->siteRepository,
            $this->domainRepository,
            $this->persistenceManager
        );
    }
}
