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
     * This command publishes all modified, created or deleted nodes in the specified workspace to its base workspace.
     * If a target workspace is specified, the content is published to that workspace instead.
     *
     * @param string $workspace Name of the workspace containing the changes to publish, for example "user-john"
     * @param string $targetWorkspace If specified, the content will be published to this workspace instead of the base workspace
     * @param boolean $verbose If enabled, some information about individual nodes will be displayed
     * @param boolean $dryRun If set, only displays which nodes would be published, no real changes are committed
     * @return void
     */
    public function publishCommand($workspace, $targetWorkspace = null, $verbose = false, $dryRun = false)
    {
        $workspaceName = $workspace;
        $workspace = $this->workspaceRepository->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" does not exist', array($workspaceName));
            $this->quit(1);
        }

        if ($targetWorkspace === null) {
            $targetWorkspace = $workspace->getBaseWorkspace();
            $targetWorkspaceName = $targetWorkspace->getName();
        } else {
            $targetWorkspaceName = $targetWorkspace;
            $targetWorkspace = $this->workspaceRepository->findOneByName($targetWorkspaceName);
            if (!$targetWorkspace instanceof Workspace) {
                $this->outputLine('Target workspace "%s" does not exist', array($targetWorkspaceName));
                $this->quit(2);
            }

            $possibleTargetWorkspaceNames = array();
            $baseWorkspace = $workspace->getBaseWorkspace();
            while ($targetWorkspace !== $baseWorkspace) {
                if ($baseWorkspace === null) {
                    $this->outputLine('The target workspace must be a base workspace of "%s".', array($targetWorkspaceName));
                    if (count($possibleTargetWorkspaceNames) > 1) {
                        $this->outputLine('For "%s" possible target workspaces currently are: %s', array($workspaceName, implode(', ', $possibleTargetWorkspaceNames)));
                    } else {
                        $this->outputLine('For "%s" the only possible target workspace currently is "%s".', array($workspaceName, reset($possibleTargetWorkspaceNames)));
                    }
                    $this->quit(3);
                }
                $possibleTargetWorkspaceNames[] = $baseWorkspace->getName();
                $baseWorkspace = $baseWorkspace->getBaseWorkspace();
            }
        }

        try {
            $nodes = $this->publishingService->getUnpublishedNodes($workspace);
        } catch (\Exception $exception) {
            $this->outputLine('An error occurred while fetching unpublished nodes from workspace %s, publish aborted.', array($workspaceName));
            $this->quit(1);
        }

        $this->outputLine('The workspace %s contains %u unpublished nodes.', array($workspaceName, count($nodes)));

        foreach ($nodes as $node) {
            /** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node */
            if ($verbose) {
                $this->outputLine('    ' . $node->getPath());
            }
            if (!$dryRun) {
                $this->publishingService->publishNode($node, $targetWorkspace);
            }
        }

        if (!$dryRun) {
            $this->outputLine('Published all nodes in workspace %s to workspace %s', array($workspaceName, $targetWorkspaceName));
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
            $this->quit(1);
        }

        try {
            $nodes = $this->publishingService->getUnpublishedNodes($workspace);
        } catch (\Exception $exception) {
            $this->outputLine('An error occurred while fetching unpublished nodes from workspace %s, discard aborted.', array($workspaceName));
            $this->quit(1);
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
     * Create a new workspace
     *
     * This command creates a new workspace.
     *
     * @param string $workspace Name of the workspace, for example "christmas-campaign"
     * @param string $baseWorkspace Name of the base workspace. If none is specified, "live" is assumed.
     * @param string $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param string $description A description explaining the purpose of the new workspace
     * @return void
     */
    public function createCommand($workspace, $baseWorkspace = 'live', $title = null, $description = null)
    {
        $workspaceName = $workspace;
        $workspace = $this->workspaceRepository->findOneByName($workspaceName);
        if ($workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" already exists', array($workspaceName));
            $this->quit(1);
        }

        $baseWorkspaceName = $baseWorkspace;
        $baseWorkspace = $this->workspaceRepository->findOneByName($baseWorkspaceName);
        if (!$baseWorkspace instanceof Workspace) {
            $this->outputLine('The base workspace "%s" does not exist', array($baseWorkspaceName));
            $this->quit(2);
        }

        if ($title === null) {
            $title = $workspaceName;
        }

        $workspace = new Workspace($workspaceName, $baseWorkspace);
        $workspace->setTitle($title);
        $workspace->setDescription($description);
        $this->workspaceRepository->add($workspace);

        $this->outputLine('Created a new workspace "%s", based on workspace "%s".', array($workspaceName, $baseWorkspaceName));
    }

    /**
     * Deletes a workspace
     *
     * This command deletes a workspace. If you only want to empty a workspace and not delete the
     * workspace itself, use <i>workspace:discard</i> instead.
     *
     * @param string $workspace Name of the workspace, for example "christmas-campaign"
     * @param boolean $force Delete the workspace and all of its contents
     * @return void
     * @see typo3.neos:workspace:discard
     */
    public function deleteCommand($workspace, $force = false)
    {
        $workspaceName = $workspace;
        $workspace = $this->workspaceRepository->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" does not exist', array($workspaceName));
            $this->quit(1);
        }

        if (substr($workspaceName, 0, 5) === 'user-') {
            $this->outputLine('Did not delete workspace "%s" because it is a user workspace. User workspaces cannot be deleted manually.', array($workspaceName));
            $this->quit(2);
        }

        $dependentWorkspaces = $this->workspaceRepository->findByBaseWorkspace($workspace);
        if (count($dependentWorkspaces) > 0) {
            $this->outputLine('Workspace "%s" cannot be deleted because the following workspaces are based on it:', array($workspaceName));
            $this->outputLine();
            $tableRows = array();
            $headerRow = array('Name', 'Title', 'Description');

            foreach ($dependentWorkspaces as $workspace) {
                $tableRows[] = array($workspace->getName(), $workspace->getTitle(), $workspace->getDescription());
            }
            $this->output->outputTable($tableRows, $headerRow);
            $this->quit(3);
        }

        try {
            $nodesCount = $this->publishingService->getUnpublishedNodesCount($workspace);
        } catch (\Exception $exception) {
            $this->outputLine('An error occurred while fetching unpublished nodes from workspace %s, nothing was deleted.', array($workspaceName));
            $this->quit(4);
        }

        if ($nodesCount > 0) {
            if ($force === false) {
                $this->outputLine('Did not delete workspace "%s" because it contains %s unpublished node(s). Use --force to delete it nevertheless.', array($workspaceName, $nodesCount));
                $this->quit(5);
            }
            $this->discardCommand($workspaceName);
        }

        $this->workspaceRepository->remove($workspace);
        $this->outputLine('Deleted workspace "%s"', array($workspaceName));
    }

    /**
     * Rebase a workspace
     *
     * This command sets a new base workspace for the specified workspace. Note that doing so will put the possible
     * changes contained in the workspace to be rebased into a different context and thus might lead to unintended
     * results when being published.
     *
     * @param string $workspace Name of the workspace to rebase, for example "user-john"
     * @param string $baseWorkspace Name of the new base workspace
     * @return void
     */
    public function rebaseCommand($workspace, $baseWorkspace)
    {
        $workspaceName = $workspace;
        $workspace = $this->workspaceRepository->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" does not exist', array($workspaceName));
            $this->quit(1);
        }

        $baseWorkspaceName = $baseWorkspace;
        $baseWorkspace = $this->workspaceRepository->findOneByName($baseWorkspaceName);
        if (!$baseWorkspace instanceof Workspace) {
            $this->outputLine('The base workspace "%s" does not exist', array($baseWorkspaceName));
            $this->quit(2);
        }

        $workspace->setBaseWorkspace($baseWorkspace);
        $this->workspaceRepository->update($workspace);

        $this->outputLine('Set "%s" as the new base workspace for "%s".', array($baseWorkspaceName, $workspaceName));
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
            $this->quit(0);
        }

        $tableRows = array();
        $headerRow = array('Name', 'Base Workspace', 'Title', 'Description');

        foreach ($workspaces as $workspace) {
            $tableRows[] = array($workspace->getName(), ($workspace->getBaseWorkspace() ? $workspace->getBaseWorkspace()->getName() : ''), $workspace->getTitle(), $workspace->getDescription());
        }
        $this->output->outputTable($tableRows, $headerRow);
    }
}
