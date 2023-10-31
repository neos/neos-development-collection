import * as React from 'react';
import { createRoot } from 'react-dom/client';
import { hot, setConfig } from 'react-hot-loader';
import ReactModal from 'react-modal';

import WorkspaceModule from './components/WorkspaceModule';
import { WorkspaceProvider } from './provider/WorkspaceProvider';
import { IntlProvider } from './provider/IntlProvider';
import { NotifyProvider } from './provider/NotifyProvider';
import ErrorBoundary from './components/ErrorBoundary';

setConfig({
    showReactDomPatchNotification: false,
});

window.onload = async (): Promise<void> => {
    while (!(window as AppWindow).NeosCMS?.I18n?.initialized) {
        await new Promise((resolve) => setTimeout(resolve, 50));
    }

    const container = document.getElementById('workspace-module-app');

    if (!container) {
        return;
    }

    const { userWorkspace, csrfToken, endpoints, userCanManageInternalWorkspaces, validation } = Object.keys(
        container.dataset
    ).reduce((carry, key) => {
        carry[key] = JSON.parse(container.dataset[key]);
        return carry;
    }, {}) as unknown as {
        userWorkspace: WorkspaceName;
        csrfToken: string;
        endpoints: WorkspaceEndpoints;
        userCanManageInternalWorkspaces: boolean;
        validation: WorkspaceValidation;
    };

    const workspacesDataElement = document.getElementById('workspaces');
    const baseWorkspaceOptionsDataElement = document.getElementById('baseWorkspaceOptions');
    const userListDataElement = document.getElementById('userList');

    if (!workspacesDataElement || !baseWorkspaceOptionsDataElement || !userListDataElement) {
        container.innerText = 'Workspace module is missing required data elements';
        return;
    }

    const workspaces = JSON.parse(document.getElementById('workspaces').textContent);
    const baseWorkspaceOptions = JSON.parse(document.getElementById('baseWorkspaceOptions').textContent);
    const userList = JSON.parse(document.getElementById('userList').textContent);

    const { I18n, Notification } = (window as AppWindow).NeosCMS;

    const translate = (id: string, label = '', args = []): string => {
        return I18n.translate(id, label, 'Shel.Neos.WorkspaceModule', 'Main', args);
    };

    ReactModal.setAppElement(container);

    // @ts-ignore
    const AppWithHmr = hot(module)(WorkspaceModule);

    const root = createRoot(container);
    root.render(
        <ErrorBoundary>
            <IntlProvider translate={translate}>
                <NotifyProvider notificationApi={Notification}>
                    <WorkspaceProvider
                        workspaceList={workspaces}
                        baseWorkspaceOptions={baseWorkspaceOptions}
                        userCanManageInternalWorkspaces={userCanManageInternalWorkspaces}
                        userList={userList}
                        userWorkspace={userWorkspace}
                        endpoints={endpoints}
                        csrfToken={csrfToken}
                        validation={validation}
                        translate={translate}
                    >
                        <AppWithHmr />
                    </WorkspaceProvider>
                </NotifyProvider>
            </IntlProvider>
        </ErrorBoundary>
    );
};
