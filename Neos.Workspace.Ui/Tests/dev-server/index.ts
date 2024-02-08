import { loadFixtures } from './fixtures';

(() => {
    console.info('Started Workspace Module dev server script');

    const container = document.getElementById('workspace-module-app');

    // Create Neos API mock
    (window as AppWindow).NeosCMS = {
        I18n: {
            initialized: true,
            // eslint-disable-next-line @typescript-eslint/no-unused-vars,no-unused-vars
            translate: (id, fallback: string, packageKey = null, source = null, args = []) => {
                Object.keys(args).forEach((key) => (fallback = fallback.replace(`{${key}}`, args[key])));
                return fallback;
            },
        },
        Notification: {
            notice: (title) => console.log(title),
            ok: (title) => console.log(title),
            error: (title, message) => console.error(message, title),
            warning: (title, message) => console.warn(message, title),
            info: (title) => console.info(title),
        },
    };

    const fixtures = loadFixtures();

    const workspacesData = document.createElement('script');
    workspacesData.id = 'workspaces';
    workspacesData.type = 'application/json';
    workspacesData.innerText = JSON.stringify(fixtures.workspaces);
    container.appendChild(workspacesData);

    const baseWorkspaceOptionsData = document.createElement('script');
    baseWorkspaceOptionsData.id = 'baseWorkspaceOptions';
    baseWorkspaceOptionsData.type = 'application/json';
    baseWorkspaceOptionsData.innerText = JSON.stringify(fixtures.baseWorkspaceOptions);
    container.appendChild(baseWorkspaceOptionsData);

    const ownerOptionsData = document.createElement('script');
    ownerOptionsData.id = 'ownerOptions';
    ownerOptionsData.type = 'application/json';
    ownerOptionsData.innerText = '{}';
    container.appendChild(ownerOptionsData);

    container.dataset.endpoints = JSON.stringify({
        deleteWorkspace: '/deleteWorkspace?workspace=---workspace---',
        updateWorkspace: '/updateWorkspace?workspace=---workspace---',
        createWorkspace: '/createWorkspace',
        showWorkspace: '/showWorkspace?workspace=---workspace---',
        getChanges: '/getChanges',
    });
    container.dataset.userWorkspace = JSON.stringify('user-sskinner');
    container.dataset.csrfToken = JSON.stringify('abc');
    container.dataset.userCanManageInteralWorkspaces = JSON.stringify(true);
    container.dataset.validation = JSON.stringify({});
})();
