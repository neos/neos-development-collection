const generateWorkspacesData = (): WorkspaceList => {
    return {
        'user-sskinner': {
            name: 'user-sskinner',
            title: 'Sidney Skinner',
            description: 'This is my private workspace',
            owner: 'sskinner',
            baseWorkspace: {
                name: 'live',
                title: 'live',
            },
            nodeCount: 23,
            changesCounts: null,
            isPersonal: true,
            isInternal: false,
            isStale: null,
            canPublish: true,
            canManage: false,
            dependentWorkspacesCount: 0,
            creator: 'Sidney Skinner',
            lastChangedDate: '2022-05-13T15:45:43+02:00',
            lastChangedTimestamp: 1652449543,
            lastChangedBy: 'Sidney Skinner',
        },
        'workspace-1': {
            name: 'workspace-1',
            title: 'Example workspace 1',
            description: 'This is a test workspace',
            owner: '',
            baseWorkspace: {
                name: 'live',
                title: 'live',
            },
            nodeCount: 1,
            changesCounts: null,
            isPersonal: false,
            isInternal: true,
            isStale: null,
            canPublish: true,
            canManage: true,
            dependentWorkspacesCount: 2,
            creator: 'Sidney Skinner',
            lastChangedDate: '2022-05-13T16:51:34+02:00',
            lastChangedTimestamp: 1652453494,
            lastChangedBy: 'Sidney Skinner',
        },
        'workspace-1-1': {
            name: 'workspace-1-1',
            title: 'Example workspace 1-1',
            description: 'This is a test workspace inside workspace 1',
            owner: '',
            baseWorkspace: {
                name: 'workspace-1',
                title: 'Example workspace 1',
            },
            nodeCount: 8,
            changesCounts: null,
            isPersonal: true,
            isInternal: false,
            isStale: null,
            canPublish: true,
            canManage: true,
            dependentWorkspacesCount: 0,
            creator: 'Sidney Skinner',
            lastChangedDate: '2022-05-20T09:23:11+02:00',
            lastChangedTimestamp: 1653031391,
            lastChangedBy: 'Sidney Skinner',
        },
        'workspace-1-2': {
            name: 'workspace-1-2',
            title: 'Example workspace 1-2',
            description: 'This is a test workspace inside workspace 1',
            owner: '',
            baseWorkspace: {
                name: 'workspace-1',
                title: 'Example workspace 1',
            },
            nodeCount: 5,
            changesCounts: null,
            isPersonal: false,
            isInternal: true,
            isStale: null,
            canPublish: true,
            canManage: true,
            dependentWorkspacesCount: 0,
            creator: 'Ashley Payne',
            lastChangedDate: '2022-05-20T09:06:58+02:00',
            lastChangedTimestamp: 1653030418,
            lastChangedBy: 'Sidney Skinner',
        },
        'workspace-2': {
            name: 'workspace-2',
            title: 'Example workspace 2',
            description: 'This is a stale test workspace',
            owner: '',
            baseWorkspace: {
                name: 'live',
                title: 'live',
            },
            nodeCount: 3,
            changesCounts: null,
            isPersonal: true,
            isInternal: false,
            isStale: true,
            canPublish: true,
            canManage: true,
            dependentWorkspacesCount: 0,
            creator: 'Sidney Skinner',
            lastChangedDate: '2022-01-24T14:20:18+01:00',
            lastChangedTimestamp: 1643030418,
            lastChangedBy: 'Ashley Payne',
        },
        'workspace-3': {
            name: 'workspace-3',
            title: 'Example workspace 3',
            description: 'This is a shared workspace',
            owner: '',
            baseWorkspace: {
                name: 'live',
                title: 'live',
            },
            nodeCount: 1,
            changesCounts: null,
            isPersonal: false,
            isInternal: true,
            isStale: false,
            canPublish: true,
            canManage: true,
            dependentWorkspacesCount: 0,
            creator: 'Sidney Skinner',
            lastChangedDate: '2022-05-20T11:14:55+02:00',
            lastChangedTimestamp: 1653038095,
            lastChangedBy: 'Ashley Payne',
        },
    };
};

function generateChangesByWorkspace() {
    return {
        'user-sskinner': {
            new: 10,
            changed: 5,
            removed: 8,
            total: 23,
        },
        'user-apayne': {
            new: 4,
            changed: 0,
            removed: 0,
            total: 4,
        },
        'workspace-1': {
            new: 0,
            changed: 0,
            removed: 0,
            total: 0,
        },
        'workspace-1-1': {
            new: 5,
            changed: 0,
            removed: 2,
            total: 7,
        },
        'workspace-1-2': {
            new: 0,
            changed: 1,
            removed: 3,
            total: 4,
        },
        'workspace-2': {
            new: 1,
            changed: 1,
            removed: 0,
            total: 2,
        },
        'workspace-3': {
            new: 0,
            changed: 0,
            removed: 0,
            total: 0,
        },
    };
}

function generateBaseWorkspaceOptions(workspaces: WorkspaceList): BaseWorkspaceOptions {
    return Object.keys(workspaces).reduce(
        (acc, workspaceKey) => {
            acc[workspaceKey] = workspaces[workspaceKey].title;
            return acc;
        },
        {
            live: 'Live',
        }
    );
}

const loadFixtures = () => {
    const workspaces = generateWorkspacesData();

    return {
        workspaces,
        changesByWorkspace: generateChangesByWorkspace(),
        baseWorkspaceOptions: generateBaseWorkspaceOptions(workspaces),
    };
};

export { loadFixtures };
