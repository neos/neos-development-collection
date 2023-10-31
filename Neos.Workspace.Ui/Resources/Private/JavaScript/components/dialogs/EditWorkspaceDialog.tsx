import React, { useCallback, useMemo, useState } from 'react';

import { DialogHeader, StyledModal } from './StyledModal';
import { useWorkspaces } from '../../provider/WorkspaceProvider';
import WorkspaceForm from './WorkspaceForm';

const EditWorkspaceDialog: React.FC = () => {
    const { workspaces, selectedWorkspaceForEdit, setSelectedWorkspaceForEdit, updateWorkspace, translate } =
        useWorkspaces();
    const [isLoading, setIsLoading] = useState(false);

    const selectedWorkspace = useMemo(() => workspaces[selectedWorkspaceForEdit], [selectedWorkspaceForEdit]);

    const handleClose = useCallback(() => {
        setSelectedWorkspaceForEdit(null);
    }, []);

    const handleSubmit = useCallback((formData: FormData) => {
        setIsLoading(true);
        updateWorkspace(formData).then(() => {
            setSelectedWorkspaceForEdit(null);
            setIsLoading(false);
        });
    }, []);

    return selectedWorkspace ? (
        <StyledModal isOpen onRequestClose={handleClose}>
            <DialogHeader>
                {translate('dialog.edit.header', `Edit "${selectedWorkspace.title}"`, {
                    workspace: selectedWorkspace.title,
                })}
            </DialogHeader>
            <WorkspaceForm
                submitLabel={translate('dialog.action.update', 'Update')}
                enabled={!isLoading}
                onSubmit={handleSubmit}
                onCancel={handleClose}
                workspace={selectedWorkspace}
            />
        </StyledModal>
    ) : null;
};

export default React.memo(EditWorkspaceDialog);
