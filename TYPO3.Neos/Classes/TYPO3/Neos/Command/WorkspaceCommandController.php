<?php
namespace TYPO3\Neos\Command;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Neos\Service\PublishingService;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * The Workspace Command Controller
 *
 * @Flow\Scope("singleton")
 */
class WorkspaceCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var PublishingService
     */
    protected $publishingService;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * Publish changes of a workspace
     *
     * This command publishes all modified, created or deleted nodes in the specified workspace to the live workspace.
     *
     * @param string $workspace Name of the workspace containing the changes to publish, for example "user-john"
     * @param boolean $verbose If enabled, some information about individual nodes will be displayed
     * @param boolean $dryRun If set, only displays which nodes would be published, no real changes are committed
     * @return void
     */
    public function publishCommand($workspace, $verbose = false, $dryRun = false)
    {
        $workspaceName = $workspace;
        $workspace = $this->workspaceRepository->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" does not exist', array($workspaceName));
            exit(1);
        }

        try {
            $nodes = $this->publishingService->getUnpublishedNodes($workspace);
        } catch (\Exception $exception) {
            $this->outputLine('An error occurred while fetching unpublished nodes from workspace %s, publish aborted.', array($workspaceName));
            exit(1);
        }

        $this->outputLine('The workspace %s contains %u unpublished nodes.', array($workspaceName, count($nodes)));

        foreach ($nodes as $node) {
            /** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node */
            if ($verbose) {
                $this->outputLine('    ' . $node->getPath());
            }
            if (!$dryRun) {
                $this->publishingService->publishNode($node);
            }
        }

        if (!$dryRun) {
            $this->outputLine('Published all nodes in workspace %s', array($workspaceName));
        }
    }

    /**
     * Discard changes in workspace
     *
     * This command discards all modified, created or deleted nodes in the specified workspace.
     *
     * @param string $workspace Name of the workspace, for example "user-john"
     * @param boolean $verbose If enabled, information about individual nodes will be displayed
     * @param boolean $dryRun If set, only displays which nodes would be discarded, no real changes are committed
     * @return void
     */
    public function discardCommand($workspace, $verbose = false, $dryRun = false)
    {
        $workspaceName = $workspace;
        $workspace = $this->workspaceRepository->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" does not exist', array($workspaceName));
            exit(1);
        }

        try {
            $nodes = $this->publishingService->getUnpublishedNodes($workspace);
        } catch (\Exception $exception) {
            $this->outputLine('An error occurred while fetching unpublished nodes from workspace %s, discard aborted.', array($workspaceName));
            exit(1);
        }

        $this->outputLine('The workspace %s contains %u unpublished nodes.', array($workspaceName, count($nodes)));

        foreach ($nodes as $node) {
            /** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node */
            if ($node->getPath() !== '/') {
                if ($verbose) {
                    $this->outputLine('    ' . $node->getPath());
                }
                if (!$dryRun) {
                    $this->publishingService->discardNode($node);
                }
            }
        }

        if (!$dryRun) {
            $this->outputLine('Discarded all nodes in workspace %s', array($workspaceName));
        }
    }

    /**
     * Publish changes of a workspace
     *
     * This command publishes all modified, created or deleted nodes in the specified workspace to the live workspace.
     *
     * @param string $workspaceName Name of the workspace, for example "user-john"
     * @param boolean $verbose If enabled, information about individual nodes will be displayed
     * @return void
     * @deprecated since 1.2
     * @see typo3.neos:workspace:publish
     */
    public function publishAllCommand($workspaceName, $verbose = false)
    {
        $this->publishCommand($workspaceName, $verbose);
    }

    /**
     * Discard changes in workspace
     *
     * This command discards all modified, created or deleted nodes in the specified workspace.
     *
     * @param string $workspaceName Name of the workspace, for example "user-john"
     * @param boolean $verbose If enabled, information about individual nodes will be displayed
     * @return void
     * @deprecated since 1.2
     * @see typo3.neos:workspace:discard
     */
    public function discardAllCommand($workspaceName, $verbose = false)
    {
        $this->discardCommand($workspaceName, $verbose);
    }

    /**
     * Display a list of existing workspaces
     *
     * @return void
     */
    public function listCommand()
    {
        $workspaces = $this->workspaceRepository->findAll();

        if ($workspaces->count() === 0) {
            $this->outputLine('No workspaces found.');
            exit(0);
        }

        $workspaceNames = array();
        foreach ($workspaces as $workspace) {
            $workspaceNames[$workspace->getName()] = $workspace->getBaseWorkspace() ? $workspace->getBaseWorkspace()->getName() : '';
        }
        ksort($workspaceNames);

        $longestName = max(array_map('strlen', array_keys($workspaceNames)));
        $this->outputLine(' <b>' . str_pad('Workspace', $longestName + 10) . 'Base workspace</b>');
        foreach ($workspaceNames as $workspaceName => $baseWorkspaceName) {
            $this->outputLine(' ' . str_pad($workspaceName, $longestName + 10) . $baseWorkspaceName);
        }
    }
}
