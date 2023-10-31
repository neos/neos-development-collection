import React, { createContext, ReactNode, useCallback, useContext, useEffect, useState } from 'react';

import { SortBy } from '../components/WorkspaceTable';
import { useNotify } from './NotifyProvider';

type WorkspaceProviderProps = {
    children: ReactNode;
    userWorkspace: WorkspaceName;
    workspaceList: WorkspaceList;
    baseWorkspaceOptions: BaseWorkspaceOptions;
    userList: UserList;
    endpoints: WorkspaceEndpoints;
    csrfToken: string;
    userCanManageInternalWorkspaces: boolean;
    validation: WorkspaceValidation;
    translate: (id: string, label?: string, args?: any[] | Record<string, string | number>) => string;
};

type WorkspaceValues = {
    userWorkspace: WorkspaceName;
    workspaces: WorkspaceList;
    setWorkspaces: (workspaces: WorkspaceList) => void;
    loadChangesCounts: () => void;
    createWorkspace: (formData: FormData) => Promise<void>;
    deleteWorkspace: (workspaceName: WorkspaceName) => void;
    updateWorkspace: (formData: FormData) => Promise<void>;
    showWorkspace: (workspaceName: WorkspaceName) => void;
    sorting: SortBy;
    setSorting: (sortBy: SortBy) => void;
    selectedWorkspaceForDeletion: WorkspaceName | null;
    setSelectedWorkspaceForDeletion: (workspaceName: WorkspaceName | null) => void;
    selectedWorkspaceForEdit: WorkspaceName | null;
    setSelectedWorkspaceForEdit: (workspaceName: WorkspaceName | null) => void;
    csrfToken: string;
    baseWorkspaceOptions: BaseWorkspaceOptions;
    userList: UserList;
    userCanManageInternalWorkspaces: boolean;
    creationDialogVisible: boolean;
    setCreationDialogVisible: (visible: boolean) => void;
    validation: WorkspaceValidation;
    translate: (id: string, label?: string, args?: any[] | Record<string, string | number>) => string;
};

const WorkspaceContext = createContext(null);
export const useWorkspaces = (): WorkspaceValues => useContext(WorkspaceContext);

export const WorkspaceProvider = ({
    userWorkspace,
    endpoints,
    workspaceList,
    userList,
    baseWorkspaceOptions: initialBaseWorkspaceOptions,
    csrfToken,
    children,
    userCanManageInternalWorkspaces,
    validation,
    translate,
}: WorkspaceProviderProps) => {
    const [baseWorkspaceOptions, setBaseWorkspaceOptions] = React.useState(initialBaseWorkspaceOptions);
    const [workspaces, setWorkspaces] = React.useState(workspaceList);
    const [sorting, setSorting] = useState<SortBy>(SortBy.lastModified);
    const [selectedWorkspaceForDeletion, setSelectedWorkspaceForDeletion] = useState<WorkspaceName | null>(null);
    const [selectedWorkspaceForEdit, setSelectedWorkspaceForEdit] = useState<WorkspaceName | null>(null);
    const [creationDialogVisible, setCreationDialogVisible] = useState(false);
    const notify = useNotify();

    const handleFlashMessages = useCallback(
        (messages: FlashMessage[]) => {
            messages.forEach(({ title, message, severity }) => {
                switch (severity.toLowerCase()) {
                    case 'ok':
                        notify.ok(title || message);
                        break;
                    case 'warning':
                        notify.warning(title, message);
                        break;
                    case 'error':
                        notify.error(title, message);
                        break;
                    default:
                        notify.info(title || message);
                }
            });
        },
        [notify]
    );

    const loadChangesCounts = useCallback(() => {
        if (!workspaces) return;
        fetch(endpoints.getChanges, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json; charset=UTF-8',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                const { changesByWorkspace }: { changesByWorkspace: Record<WorkspaceName, ChangesCounts> } = data;
                const updatedWorkspaces = Object.keys(workspaces).reduce<WorkspaceList>(
                    (carry: WorkspaceList, workspaceName) => {
                        const changesCounts = changesByWorkspace[workspaceName];
                        if (changesCounts) {
                            carry[workspaceName] = { ...workspaces[workspaceName], changesCounts };
                        } else {
                            carry[workspaceName] = workspaces[workspaceName];
                        }
                        return carry;
                    },
                    {} as WorkspaceList
                );
                setWorkspaces(updatedWorkspaces);
            })
            .catch((error) => {
                notify.error('Failed to load changes for workspaces', error.message);
                console.error('Failed to load changes for workspaces', error);
            });
    }, [endpoints]);

    const prepareWorkspaceActionUrl = useCallback((endpoint: string, workspaceName: WorkspaceName) => {
        return endpoint.replace('---workspace---', workspaceName);
    }, []);

    const deleteWorkspace = useCallback(
        async (workspaceName: string): Promise<void> => {
            return fetch(prepareWorkspaceActionUrl(endpoints.deleteWorkspace, workspaceName), {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json; charset=UTF-8',
                },
                body: JSON.stringify({ __csrfToken: csrfToken }),
            })
                .then((response) => response.json())
                .then(
                    ({
                        success,
                        rebasedWorkspaces,
                        messages = [],
                    }: {
                        success: boolean;
                        rebasedWorkspaces: Workspace[];
                        messages: FlashMessage[];
                    }) => {
                        if (success) {
                            setWorkspaces((workspaces) => {
                                const updatedWorkspaces = { ...workspaces };

                                if (updatedWorkspaces[workspaceName].baseWorkspace.name !== 'live') {
                                    // Update dependent workspace count of previous base workspace
                                    updatedWorkspaces[updatedWorkspaces[workspaceName].baseWorkspace.name]
                                        .dependentWorkspacesCount--;
                                }

                                // Removed deleted workspace from list
                                delete updatedWorkspaces[workspaceName];

                                // Update base workspace for all rebased workspaces
                                rebasedWorkspaces.forEach((rebasedWorkspace) => {
                                    if (updatedWorkspaces[rebasedWorkspace.name]) {
                                        updatedWorkspaces[rebasedWorkspace.name].baseWorkspace = {
                                            name: 'live',
                                            title: 'Live',
                                        };
                                    }
                                });
                                return updatedWorkspaces;
                            });
                            // Remove the workspace also from the list of selectable base workspaces
                            setBaseWorkspaceOptions((baseWorkspaceOptions) => {
                                if (baseWorkspaceOptions[workspaceName]) {
                                    delete baseWorkspaceOptions[workspaceName];
                                }
                                return baseWorkspaceOptions;
                            });
                        }
                        handleFlashMessages(messages);
                    }
                )
                .catch((error) => {
                    notify.error('Failed to delete workspace', error.message);
                    console.error('Failed to delete workspace', error);
                });
        },
        [csrfToken, endpoints.deleteWorkspace]
    );

    const updateWorkspace = useCallback(
        async (formData: FormData): Promise<void> => {
            return fetch(endpoints.updateWorkspace, {
                method: 'POST',
                credentials: 'include',
                body: formData,
            })
                .then((response) => response.json())
                .then(
                    ({
                        success,
                        workspace,
                        messages = [],
                        baseWorkspaceOptions,
                    }: {
                        success: boolean;
                        workspace: Workspace;
                        messages: FlashMessage[];
                        baseWorkspaceOptions: BaseWorkspaceOptions;
                    }) => {
                        if (success) {
                            // Keep old changes counts after updating workspace with remote data
                            setWorkspaces((workspaces) => {
                                // Update dependent workspace count of previous and new base workspace
                                if (workspaces[workspace.name].baseWorkspace.name !== workspace.baseWorkspace.name) {
                                    if (workspaces[workspace.name].baseWorkspace.name !== 'live') {
                                        workspaces[workspaces[workspace.name].baseWorkspace.name]
                                            .dependentWorkspacesCount--;
                                    }
                                    if (workspace.baseWorkspace.name !== 'live') {
                                        workspaces[workspace.baseWorkspace.name].dependentWorkspacesCount++;
                                    }
                                }

                                return {
                                    ...workspaces,
                                    [workspace.name]: {
                                        ...workspaces[workspace.name],
                                        ...workspace,
                                        changesCounts: workspaces[workspace.name].changesCounts,
                                    },
                                };
                            });
                        }
                        setBaseWorkspaceOptions(baseWorkspaceOptions);
                        handleFlashMessages(messages);
                        return workspace[workspace.name];
                    }
                )
                .catch((error) => {
                    notify.error('Failed to update workspace', error.message);
                    console.error('Failed to update workspace', error);
                });
        },
        [csrfToken, endpoints.updateWorkspace]
    );

    const createWorkspace = useCallback(
        async (formData: FormData): Promise<void> => {
            return fetch(endpoints.createWorkspace, {
                method: 'POST',
                credentials: 'include',
                body: formData,
            })
                .then((response) => response.json())
                .then(
                    ({
                        success,
                        workspace,
                        messages,
                        baseWorkspaceOptions,
                    }: {
                        success: boolean;
                        workspace: Workspace;
                        messages: FlashMessage[];
                        baseWorkspaceOptions: BaseWorkspaceOptions;
                    }) => {
                        if (success) {
                            setWorkspaces((workspaces) => {
                                const updatedWorkspaces = {
                                    ...workspaces,
                                    [workspace.name]: {
                                        ...workspace,
                                        // Set changes to zero, we don't need to fetch it from server
                                        changesCounts: {
                                            changed: 0,
                                            new: 0,
                                            removed: 0,
                                            total: 0,
                                        },
                                    },
                                };
                                if (workspace.baseWorkspace.name !== 'live') {
                                    // Update dependent workspace count on base workspace
                                    updatedWorkspaces[workspace.baseWorkspace.name].dependentWorkspacesCount++;
                                }
                                return updatedWorkspaces;
                            });
                            setBaseWorkspaceOptions(baseWorkspaceOptions);
                        }
                        handleFlashMessages(messages);
                    }
                )
                .catch((error) => {
                    notify.error('Failed to create workspace', error.message);
                    console.error('Failed to create workspace', error);
                });
        },
        [csrfToken, endpoints.createWorkspace]
    );

    const showWorkspace = useCallback((workspaceName: string) => {
        window.open(prepareWorkspaceActionUrl(endpoints.showWorkspace, workspaceName), '_self');
    }, []);

    useEffect(() => {
        if (workspaceList) loadChangesCounts();
    }, []);

    return (
        <WorkspaceContext.Provider
            value={{
                userWorkspace,
                workspaces,
                setWorkspaces,
                baseWorkspaceOptions,
                userList: userList,
                loadChangesCounts,
                deleteWorkspace,
                updateWorkspace,
                showWorkspace,
                sorting,
                setSorting,
                selectedWorkspaceForDeletion,
                setSelectedWorkspaceForDeletion,
                selectedWorkspaceForEdit,
                setSelectedWorkspaceForEdit,
                csrfToken,
                userCanManageInternalWorkspaces,
                creationDialogVisible,
                setCreationDialogVisible,
                validation,
                createWorkspace,
                translate,
            }}
        >
            {children}
        </WorkspaceContext.Provider>
    );
};
