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

namespace Neos\Neos\Command;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeNameIsAlreadyCovered;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Exception\SiteNodeNameIsAlreadyInUseByAnotherSite;
use Neos\Neos\Domain\Exception\SiteNodeTypeIsInvalid;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\SiteExportService;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\Domain\Service\SitePruningService;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Utility\Files;

/**
 * The Site Command Controller
 *
 * @Flow\Scope("singleton")
 */
class SiteCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var SiteService
     */
    protected $siteService;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var SiteImportService
     */
    protected $siteImportService;

    /**
     * @Flow\Inject
     * @var SiteExportService
     */
    protected $siteExportService;

    /**
     * @Flow\Inject
     * @var SitePruningService
     */
    protected $sitePruningService;

    /**
     * @Flow\Inject
     * @var WorkspaceService
     */
    protected $workspaceService;

    /**
     * Create a new site
     *
     * This command allows to create a blank site with just a single empty document in the default dimension.
     * The name of the site, the packageKey must be specified.
     *
     * The node type given with the ``nodeType`` option must already exists
     * and have the superType ``Neos.Neos:Document``.
     *
     * If no ``nodeName`` option is specified the command will create a unique node-name from the name of the site.
     * If a node name is given it has to be unique for the setup.
     *
     * If the flag ``activate`` is set to false new site will not be activated.
     *
     * @param string $name The name of the site
     * @param string $packageKey The site package
     * @param string $nodeType The node type to use for the site node, e.g. Amce.Com:Page
     * @param string $nodeName The name of the site node.
     *                         If no nodeName is given it will be determined from the siteName.
     * @param boolean $inactive The new site is not activated immediately (default = false)
     * @return void
     */
    public function createCommand($name, $packageKey, $nodeType, $nodeName = null, $inactive = false)
    {
        if ($this->packageManager->isPackageAvailable($packageKey) === false) {
            $this->outputLine('<error>Could not find package "%s"</error>', [$packageKey]);
            $this->quit(1);
        }

        try {
            $this->siteService->createSite($packageKey, $name, $nodeType, $nodeName, $inactive);
        } catch (NodeTypeNotFound $exception) {
            $this->outputLine('<error>The given node type "%s" was not found</error>', [$nodeType]);
            $this->quit(1);
        } catch (SiteNodeTypeIsInvalid $exception) {
            $this->outputLine(
                '<error>The given node type "%s" is not based on the superType "%s"</error>',
                [$nodeType, NodeTypeNameFactory::NAME_SITE]
            );
            $this->quit(1);
        } catch (SiteNodeNameIsAlreadyInUseByAnotherSite | NodeNameIsAlreadyCovered $exception) {
            $this->outputLine('<error>A site with siteNodeName "%s" already exists</error>', [$nodeName ?: $name]);
            $this->quit(1);
        }

        $this->outputLine(
            'Successfully created site "%s" with siteNode "%s", type "%s", packageKey "%s" and state "%s"',
            [$name, $nodeName ?: $name, $nodeType, $packageKey, $inactive ? 'offline' : 'online']
        );
    }

    /**
     * Import sites
     *
     * This command allows importing sites from the given path/package. The format must
     * be identical to that produced by the exportAll command.
     *
     * If a path is specified, this command expects the corresponding directory to contain the exported files
     *
     * If a package key is specified, this command expects the export files to be located in the private resources
     * directory of the given package (Resources/Private/Content).
     *
     * **Note that the live workspace has to be empty prior to importing.**
     *
     * @param string|null $packageKey Package key specifying the package containing the sites content
     * @param string|null $path relative or absolute path and filename to the export files
     * @return void
     */
    public function importAllCommand(?string $packageKey = null, ?string $path = null, string $contentRepository = 'default', bool $verbose = false): void
    {
        // TODO check if this warning is still necessary with Neos 9
        // Since this command uses a lot of memory when large sites are imported, we warn the user to watch for
        // the confirmation of a successful import.
        $this->outputLine('<b>This command can use a lot of memory when importing sites with many resources.</b>');
        $this->outputLine('If the import is successful, you will see a message saying "Import finished".');
        $this->outputLine('If you do not see this message, the import failed, most likely due to insufficient memory.');
        $this->outputLine('Increase the <b>memory_limit</b> configuration parameter of your php CLI to attempt to fix this.');
        $this->outputLine('Starting import...');
        $this->outputLine('---');

        $path = $this->determineTargetPath($packageKey, $path);

        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        $this->siteImportService->importFromPath(
            $contentRepositoryId,
            $path,
            $this->createOnProcessorClosure(),
            $this->createOnMessageClosure($verbose)
        );

        $this->outputLine('Import finished.');
    }

    /**
     * Export sites
     *
     * This command exports all sites of the content repository.
     *
     * If a path is specified, this command creates the directory if needed and exports into that.
     *
     * If a package key is specified, this command exports to the private resources
     * directory of the given package (Resources/Private/Content).
     *
     * @param string|null $packageKey Package key specifying the package containing the sites content
     * @param string|null $path relative or absolute path and filename to the export files
     * @return void
     */
    public function exportAllCommand(?string $packageKey = null, ?string $path = null, string $contentRepository = 'default', bool $verbose = false): void
    {
        $path = $this->determineTargetPath($packageKey, $path);
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        Files::createDirectoryRecursively($path);
        $this->siteExportService->exportToPath(
            $contentRepositoryId,
            $path,
            $this->createOnProcessorClosure(),
            $this->createOnMessageClosure($verbose)
        );
    }

    /**
     * This will completely prune the data of the specified content repository and remove all site-records.
     *
     * @param bool $force Prune the cr without confirmation. This cannot be reverted!
     * @return void
     */
    public function pruneAllCommand(string $contentRepository = 'default', bool $force = false, bool $verbose = false): void
    {
        if (!$force && !$this->output->askConfirmation(sprintf('> This will prune your content repository "%s" and all its attached sites. Are you sure to proceed? (y/n) ', $contentRepository), false)) {
            $this->outputLine('<comment>Abort.</comment>');
            return;
        }
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        $this->sitePruningService->pruneAll(
            $contentRepositoryId,
            $this->createOnProcessorClosure(),
            $this->createOnMessageClosure($verbose)
        );
    }

    /**
     * List available sites
     *
     * @return void
     * @throws StopCommandException
     */
    public function listCommand(): void
    {
        $sites = $this->siteRepository->findAll();
        if ($sites->count() === 0) {
            $this->outputLine('No sites available');
            $this->quit();
        }

        $tableRows = [];
        $tableHeaderRows = ['Name', 'Node name', 'Resource package', 'Status'];
        foreach ($sites as $site) {
            $siteStatus = ($site->getState() === SITE::STATE_ONLINE) ? 'online' : 'offline';
            $tableRows[] = [$site->getName(), $site->getNodeName(), $site->getSiteResourcesPackageKey(), $siteStatus];
        }
        $this->output->outputTable($tableRows, $tableHeaderRows);
    }

    /**
     * Activate a site (with globbing)
     *
     * This command activates the specified site.
     *
     * @param string $siteNode The node name of the sites to activate (globbing is supported)
     * @return void
     */
    public function activateCommand($siteNode)
    {
        $sites = $this->findSitesByNodeNamePattern($siteNode);
        if (empty($sites)) {
            $this->outputLine('<error>No Site found for pattern "%s".</error>', [$siteNode]);
            $this->quit(1);
        }
        foreach ($sites as $site) {
            $site->setState(Site::STATE_ONLINE);
            $this->siteRepository->update($site);
            $this->outputLine('Site "%s" was activated.', [$site->getNodeName()]);
        }
    }

    /**
     * Deactivate a site (with globbing)
     *
     * This command deactivates the specified site.
     *
     * @param string $siteNode The node name of the sites to deactivate (globbing is supported)
     * @return void
     */
    public function deactivateCommand($siteNode)
    {
        $sites = $this->findSitesByNodeNamePattern($siteNode);
        if (empty($sites)) {
            $this->outputLine('<error>No Site found for pattern "%s".</error>', [$siteNode]);
            $this->quit(1);
        }
        foreach ($sites as $site) {
            $site->setState(Site::STATE_OFFLINE);
            $this->siteRepository->update($site);
            $this->outputLine('Site "%s" was deactivated.', [$site->getNodeName()]);
        }
    }

    /**
     * Find all sites the match the given site-node-name-pattern with support for globbing
     *
     * @param string $siteNodePattern nodeName patterns for sites to find
     * @return array<Site>
     */
    protected function findSitesByNodeNamePattern($siteNodePattern)
    {
        return array_filter(
            $this->siteRepository->findAll()->toArray(),
            function (Site $site) use ($siteNodePattern) {
                return fnmatch($siteNodePattern, $site->getNodeName()->value);
            }
        );
    }

    protected function determineTargetPath(?string $packageKey, ?string $path): string
    {
        $exceedingArguments = $this->request->getExceedingArguments();
        if (isset($exceedingArguments[0]) && $packageKey === null && $path === null) {
            if (file_exists($exceedingArguments[0])) {
                $path = $exceedingArguments[0];
            } elseif ($this->packageManager->isPackageAvailable($exceedingArguments[0])) {
                $packageKey = $exceedingArguments[0];
            }
        }
        if ($packageKey === null && $path === null) {
            $this->outputLine('<error>You have to specify either <em>--package-key</em> or <em>--path</em></error>');
            $this->quit(1);
        }
        if ($path === null) {
            $package = $this->packageManager->getPackage($packageKey);
            $path = Files::concatenatePaths([$package->getPackagePath(), 'Resources/Private/Content']);
        }
        if (str_starts_with($path, 'resource://')) {
            $this->outputLine('<error>Resource paths are not allowed, please use <em>--package-key</em> instead or a real path.</error>');
            $this->quit(1);
        }
        return $path;
    }

    protected function createOnProcessorClosure(): \Closure
    {
        $onProcessor = function (string $processorLabel) {
            $this->outputLine('<info>%s...</info>', [$processorLabel]);
        };
        return $onProcessor;
    }

    protected function createOnMessageClosure(bool $verbose): \Closure
    {
        return function (Severity $severity, string $message) use ($verbose) {
            if (!$verbose && $severity === Severity::NOTICE) {
                return;
            }
            $this->outputLine(match ($severity) {
                Severity::NOTICE => $message,
                Severity::WARNING => sprintf('<comment>Warning: %s</comment>', $message),
                Severity::ERROR => sprintf('<error>Error: %s</error>', $message),
            });
        };
    }
}
