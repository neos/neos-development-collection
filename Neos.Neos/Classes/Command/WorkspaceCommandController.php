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

use Neos\ContentRepository\Feature\Common\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist;
use Neos\ContentRepository\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Command\DiscardWorkspace;
use Neos\ContentRepository\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;

/**
 * The Workspace Command Controller
 *
 * @Flow\Scope("singleton")
 */
class WorkspaceCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    #[Flow\Inject]
    protected WorkspaceCommandHandler $workspaceCommandHandler;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    /**
     * Publish changes of a workspace
     *
     * This command publishes all modified, created or deleted nodes in the specified workspace to its base workspace.
     * If a target workspace is specified, the content is published to that workspace instead.
     *
     * @param string $workspace Name of the workspace containing the changes to publish, for example "user-john"
     * @param string $targetWorkspace If specified, the content will be published to this workspace
     *                                instead of the base workspace
     * @param boolean $verbose If enabled, some information about individual nodes will be displayed
     * @param boolean $dryRun If set, only displays which nodes would be published, no real changes are committed
     * @return void
     */
    public function publishCommand($workspace, $targetWorkspace = null, $verbose = false, $dryRun = false)
    {
        /* @todo how do we do this?
        $workspaceName = $workspace;
        $workspace = $this->workspaceFinder->findOneByName(WorkspaceName::fromString($workspaceName));
        if (!$workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" does not exist', [$workspaceName]);
            $this->quit(1);
        }

        if ($targetWorkspace === null) {
            $targetWorkspace = $workspace->getBaseWorkspace();
            $targetWorkspaceName = $targetWorkspace->getName();
        } else {
            $targetWorkspaceName = $targetWorkspace;
            $targetWorkspace = $this->workspaceFinder->findOneByName(
                WorkspaceName::fromString($targetWorkspaceName)
            );
            if (!$targetWorkspace instanceof Workspace) {
                $this->outputLine('Target workspace "%s" does not exist', [$targetWorkspaceName]);
                $this->quit(2);
            }

            $possibleTargetWorkspaceNames = [];
            $baseWorkspace = $workspace->getBaseWorkspace();
            while ($targetWorkspace !== $baseWorkspace) {
                if ($baseWorkspace === null) {
                    $this->outputLine('The target workspace must be a base workspace of "%s".', [$targetWorkspaceName]);
                    if (count($possibleTargetWorkspaceNames) > 1) {
                        $this->outputLine(
                            'For "%s" possible target workspaces currently are: %s',
                            [$workspaceName, implode(', ', $possibleTargetWorkspaceNames)]
                        );
                    } else {
                        $this->outputLine(
                            'For "%s" the only possible target workspace currently is "%s".',
                            [$workspaceName, reset($possibleTargetWorkspaceNames)]
                        );
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
            $this->outputLine(
                'An error occurred while fetching unpublished nodes from workspace %s, publish aborted.',
                [$workspaceName]
            );
            $this->quit(1);
        }

        $amount = count($nodes);
        $this->outputLine('The workspace %s contains %u unpublished nodes.', [$workspaceName, $amount]);

        foreach ($nodes as $index => $node) {
            if ($verbose) {
                $this->outputLine("[%s][%s/%u] %s", [
                    date('H:i:s'),
                    str_pad($index + 1, strlen($amount . ''), ' ', STR_PAD_LEFT),
                    $amount,
                    $node->getContextPath()
                ]);
            }
            if (!$dryRun) {
                $this->publishingService->publishNode($node, $targetWorkspace);
            }
        }*/

        if (!$dryRun) {
            $this->workspaceCommandHandler->handlePublishWorkspace(new PublishWorkspace(
                WorkspaceName::fromString($workspace),
                UserIdentifier::forSystemUser()
            ));

            $this->outputLine(
                'Published all nodes in workspace %s to its base workspace',
                [$workspace]
            );
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
        /* @todo how do we check this?
        try {
            $nodes = $this->publishingService->getUnpublishedNodes($workspace);
        } catch (\Exception $exception) {
            $this->outputLine(
                'An error occurred while fetching unpublished nodes from workspace %s, discard aborted.',
                [$workspace]
            );
            $this->quit(1);
        }*/

        #$this->outputLine('The workspace %s contains %u unpublished nodes.', [$workspace, count($nodes)]);

        /*
        foreach ($nodes as $node) {
            if ($node->getPath() !== '/') {
                if ($verbose) {
                    $this->outputLine('    ' . $node->getPath());
                }
                if (!$dryRun) {
                    $this->publishingService->discardNode($node);
                }
            }
        }*/

        if (!$dryRun) {
            try {
                $this->workspaceCommandHandler->handleDiscardWorkspace(DiscardWorkspace::create(
                    WorkspaceName::fromString($workspace),
                    UserIdentifier::forSystemUser()
                ));
            } catch (WorkspaceDoesNotExist $exception) {
                $this->outputLine('Workspace "%s" does not exist', [$workspace]);
                $this->quit(1);
            }
            $this->outputLine('Discarded all nodes in workspace %s', [$workspace]);
        }
    }

    public function createRootCommand(string $name): void
    {
        $this->workspaceCommandHandler->handleCreateRootWorkspace(new CreateRootWorkspace(
            WorkspaceName::fromString($name),
            WorkspaceTitle::fromString($name),
            WorkspaceDescription::fromString($name),
            UserIdentifier::forSystemUser(),
            ContentStreamIdentifier::create()
        ));
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
     * @param string $owner The identifier of a User to own the workspace
     * @return void
     */
    public function createCommand($workspace, $baseWorkspace = 'live', $title = null, $description = null, $owner = '')
    {
        if ($owner === '') {
            $workspaceOwner = null;
        } else {
            $workspaceOwner = $this->userService->getCurrentUserIdentifier();
            if ($workspaceOwner === null) {
                $this->outputLine('The user "%s" specified as owner does not exist', [$owner]);
                $this->quit(3);
            }
        }

        try {
            $this->workspaceCommandHandler->handleCreateWorkspace(new CreateWorkspace(
                WorkspaceName::fromString($workspace),
                WorkspaceName::fromString($baseWorkspace),
                WorkspaceTitle::fromString($title ?: $workspace),
                WorkspaceDescription::fromString($description ?: $workspace),
                UserIdentifier::forSystemUser(),
                null,
                $workspaceOwner
            ));
        } catch (WorkspaceAlreadyExists $workspaceAlreadyExists) {
            $this->outputLine('Workspace "%s" already exists', [$workspace]);
            $this->quit(1);
        } catch (BaseWorkspaceDoesNotExist $baseWorkspaceDoesNotExist) {
            $this->outputLine('The base workspace "%s" does not exist', [$baseWorkspace]);
            $this->quit(2);
        }

        if ($workspaceOwner instanceof UserIdentifier) {
            $this->outputLine(
                'Created a new workspace "%s", based on workspace "%s", owned by "%s".',
                [$workspace, $baseWorkspace, $owner]
            );
        } else {
            $this->outputLine(
                'Created a new workspace "%s", based on workspace "%s".',
                [$workspace, $baseWorkspace]
            );
        }
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
     * @see neos.neos:workspace:discard
     */
    public function deleteCommand($workspace, $force = false)
    {
        throw new \BadMethodCallException(
            'Workspace removal is not supported yet',
            1651961301
        );
        /*
        $workspaceName = WorkspaceName::fromString($workspace);
        if ($workspaceName->isLive()) {
            $this->outputLine('Did not delete workspace "live" because it is required for Neos CMS to work properly.');
            $this->quit(2);
        }

        $workspace = $this->workspaceFinder->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" does not exist', [$workspaceName]);
            $this->quit(1);
        }

        if ($workspace->isPersonalWorkspace()) {
            $this->outputLine(
                'Did not delete workspace "%s" because it is a personal workspace.'
                    . ' Personal workspaces cannot be deleted manually.',
                [$workspaceName]
            );
            $this->quit(2);
        }

        $dependentWorkspaces = $this->workspaceFinder->findByBaseWorkspace($workspace);
        if (count($dependentWorkspaces) > 0) {
            $this->outputLine(
                'Workspace "%s" cannot be deleted because the following workspaces are based on it:',
                [$workspaceName]
            );
            $this->outputLine();
            $tableRows = [];
            $headerRow = ['Name', 'Title', 'Description'];

            foreach ($dependentWorkspaces as $dependentWorkspace) {
                $tableRows[] = [
                    $dependentWorkspace->getWorkspaceName(),
                    $dependentWorkspace->getWorkspaceTitle(),
                    $dependentWorkspace->getWorkspaceDescription()
                ];
            }
            $this->output->outputTable($tableRows, $headerRow);
            $this->quit(3);
        }

        try {
            $nodesCount = $this->publishingService->getUnpublishedNodesCount($workspace);
            $nodesCount = 0;
        } catch (\Exception $exception) {
            $this->outputLine(
                'An error occurred while fetching unpublished nodes from workspace %s, nothing was deleted.',
                [$workspaceName]
            );
            $this->quit(4);
        }

        if ($nodesCount > 0) {
            if ($force === false) {
                $this->outputLine(
                    'Did not delete workspace "%s" because it contains %s unpublished node(s).'
                        . ' Use --force to delete it nevertheless.',
                    [$workspaceName, $nodesCount]
                );
                $this->quit(5);
            }
            $this->discardCommand($workspaceName);
        }

        $this->workspaceCommandHandler->handlePublishIndividualNodesFromWorkspace()


        $this->workspaceFinder->remove($workspace);
        $this->outputLine('Deleted workspace "%s"', [$workspaceName]);
        */
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
        throw new \BadMethodCallException(
            'Workspace rebasing is now a different concept ("real", git-like rebase <3),'
                . ' changing the base workspace is not yet supported',
            1651960852
        );
        /*
        $workspaceName = $workspace;
        $workspace = $this->workspaceFinder->findOneByName(WorkspaceName::fromString($workspaceName));
        if (!$workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" does not exist', [$workspaceName]);
            $this->quit(1);
        }

        if ($workspace->getName() === 'live') {
            $this->outputLine('The workspace "live" cannot be rebased as it is the global base workspace.');
            $this->quit(2);
        }

        $baseWorkspaceName = $baseWorkspace;
        $baseWorkspace = $this->workspaceFinder->findOneByName($baseWorkspaceName);
        if (!$baseWorkspace instanceof Workspace) {
            $this->outputLine('The base workspace "%s" does not exist', [$baseWorkspaceName]);
            $this->quit(2);
        }

        $workspace->setBaseWorkspace($baseWorkspace);
        $this->workspaceFinder->update($workspace);

        $this->outputLine('Set "%s" as the new base workspace for "%s".', [$baseWorkspaceName, $workspaceName]);
        */
    }

    /**
     * Display a list of existing workspaces
     *
     * @return void
     */
    public function listCommand()
    {
        $workspaces = $this->workspaceFinder->findAll();

        if (count($workspaces) === 0) {
            $this->outputLine('No workspaces found.');
            $this->quit(0);
        }

        $tableRows = [];
        $headerRow = ['Name', 'Base Workspace', 'Title', 'Owner', 'Description'];

        foreach ($workspaces as $workspace) {
            $tableRows[] = [
                $workspace->getWorkspaceName(),
                $workspace->getBaseWorkspaceName() ?: '',
                $workspace->getWorkspaceTitle(),
                $workspace->getWorkspaceOwner() ?: '',
                $workspace->getWorkspaceDescription()
            ];
        }
        $this->output->outputTable($tableRows, $headerRow);
    }
}
