<?php
namespace Neos\EventSourcedNeosAdjustments\NodeImportFromLegacyCR\Command;

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedNeosAdjustments\NodeImportFromLegacyCR\Service\ContentRepositoryExportService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

/**
 * @Flow\Scope("singleton")
 */
class ContentRepositoryMigrateCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var ContentRepositoryExportService
     */
    protected $contentRepositoryExportService;

    /**
     * Run a CR export
     */
    public function runCommand()
    {
        $this->contentRepositoryExportService->reset();
        $this->contentRepositoryExportService->migrate();
    }
}
