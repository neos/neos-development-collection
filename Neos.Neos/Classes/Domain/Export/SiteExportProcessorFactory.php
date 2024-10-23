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

namespace Neos\Neos\Domain\Export;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\Domain\Import\LiveWorkspaceIsEmptyProcessor;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * @implements ContentRepositoryServiceFactoryInterface<SiteExportProcessor>
 */
final readonly class SiteExportProcessorFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private WorkspaceName $workspaceName,
        private SiteRepository $siteRepository,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        return new SiteExportProcessor(
            $serviceFactoryDependencies->contentRepository,
            $this->workspaceName,
            $this->siteRepository,
        );
    }
}
