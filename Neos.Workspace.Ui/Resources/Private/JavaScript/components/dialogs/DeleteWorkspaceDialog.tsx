import React, { useCallback, useMemo } from 'react';
import styled from 'styled-components';

import { useWorkspaces } from '../../provider/WorkspaceProvider';
import { DialogHeader, StyledModal, ActionBar } from './StyledModal';

const RebasedWorkspaceWrapper = styled.div`
    margin: 1rem 0;

    & ul {
        margin: 1rem 0;
    }

    & li {
        list-style-type: disc;
        margin: 0.3rem 0 0.3rem 1rem;
    }
`;

const DeleteWorkspaceDialog: React.FC = () => {
    const { selectedWorkspaceForDeletion, setSelectedWorkspaceForDeletion, deleteWorkspace, workspaces, translate } =
        useWorkspaces();

    const selectedWorkspace = useMemo(() => workspaces[selectedWorkspaceForDeletion], [selectedWorkspaceForDeletion]);
    const dependentWorkspaces = useMemo(() => {
        return Object.values(workspaces).filter(
            (workspace) => workspace.baseWorkspace.name === selectedWorkspaceForDeletion
        );
    }, [selectedWorkspaceForDeletion, workspaces]);

    const handleClose = useCallback(() => {
        setSelectedWorkspaceForDeletion(null);
    }, []);

    const handleDelete = useCallback(() => {
        deleteWorkspace(selectedWorkspaceForDeletion);
        handleClose();
    }, [selectedWorkspaceForDeletion]);

    return selectedWorkspaceForDeletion ? (
        <StyledModal isOpen onRequestClose={handleClose}>
            <DialogHeader>
                {translate('dialog.delete.header', `Delete "${selectedWorkspace.title}"?`, {
                    workspace: selectedWorkspace.title,
                })}
            </DialogHeader>

            {selectedWorkspace.changesCounts.total > 0 && (
                <p
                    dangerouslySetInnerHTML={{
                        __html: translate(
                            'dialog.delete.unpublishedChanges',
                            `Deleting this workspace will also discard ${selectedWorkspace.changesCounts.total} unpublished changes.`,
                            { count: selectedWorkspace.changesCounts.total }
                        ),
                    }}
                />
            )}
            {selectedWorkspace.dependentWorkspacesCount > 0 && (
                <RebasedWorkspaceWrapper>
                    <i
                        className="fas fa-exclamation-triangle"
                        style={{ color: 'var(--warningText)', marginRight: '.5em' }}
                    ></i>{' '}
                    <span
                        dangerouslySetInnerHTML={{
                            __html: translate(
                                'dialog.delete.rebasedWorkspaces',
                                'The following workspaces will be rebased onto the <strong>live</strong> workspace:'
                            ),
                        }}
                    />
                    <ul>
                        {dependentWorkspaces.map((child) => (
                            <li key={child.title}>{child.title}</li>
                        ))}
                        {selectedWorkspace.dependentWorkspacesCount > dependentWorkspaces.length && (
                            <li
                                dangerouslySetInnerHTML={{
                                    __html: translate(
                                        'dialog.delete.privateWorkspaces',
                                        `${
                                            selectedWorkspace.dependentWorkspacesCount - dependentWorkspaces.length
                                        } private workspace(s)`,
                                        {
                                            count:
                                                selectedWorkspace.dependentWorkspacesCount - dependentWorkspaces.length,
                                        }
                                    ),
                                }}
                            />
                        )}
                    </ul>
                </RebasedWorkspaceWrapper>
            )}
            <p>{translate('dialog.delete.pointOfNoReturn', 'This action cannot be undone.')}</p>

            <ActionBar>
                <button type="button" className="neos-button" onClick={handleClose}>
                    {translate('dialog.action.cancel', 'Cancel')}
                </button>
                <button type="button" className="neos-button neos-button-danger" onClick={handleDelete}>
                    {translate('dialog.action.delete', 'Delete')}
                </button>
            </ActionBar>
        </StyledModal>
    ) : null;
};

export default React.memo(DeleteWorkspaceDialog);
