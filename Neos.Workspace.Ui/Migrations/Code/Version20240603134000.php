<?php

namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Update privilege names related to the workspace module in custom roles used
 * in templates, configuration and code.
 */
class Version20240603134000 extends AbstractMigration
{

    public function getIdentifier(): string
    {
        return 'Neos.Workspace.Ui-20240603134000';
    }

    public function up(): void
    {
        $this->searchAndReplace(
            'Neos.Neos:Backend.PublishAllToLiveWorkspace',
            'Neos.Workspace.Ui:Backend.PublishAllToLiveWorkspace',
            ['yaml', 'html', 'php']
        );
        $this->searchAndReplace(
            'Neos.Neos:Backend.CreateWorkspaces',
            'Neos.Workspace.Ui:Backend.CreateWorkspaces',
            ['yaml', 'html', 'php']
        );
        $this->searchAndReplace(
            'Neos.Neos:Backend.Module.Management.Workspaces.ManageOwnWorkspaces',
            'Neos.Workspace.Ui:Backend.Module.Management.Workspace.ManageOwnWorkspaces',
            ['yaml', 'html', 'php']
        );
        $this->searchAndReplace(
            'Neos.Neos:Backend.Module.Management.Workspaces.ManageInternalWorkspaces',
            'Neos.Workspace.Ui:Backend.Module.Management.Workspace.ManageInternalWorkspaces',
            ['yaml', 'html', 'php']
        );
        $this->searchAndReplace(
            'Neos.Neos:Backend.Module.Management.Workspaces.ManageAllPrivateWorkspaces',
            'Neos.Workspace.Ui:Backend.Module.Management.Workspace.ManageAllPrivateWorkspaces',
            ['yaml', 'html', 'php']
        );
        $this->searchAndReplace(
            'Neos.Neos:Backend.Module.Management.Workspaces',
            'Neos.Workspace.Ui:Backend.Module.Management.Workspace',
            ['yaml', 'html', 'php']
        );
    }
}
