import React, { useCallback, useState } from 'react';

import { DialogHeader, StyledModal } from './StyledModal';
import { useWorkspaces } from '../../provider/WorkspaceProvider';
import WorkspaceForm from './WorkspaceForm';

const CreateWorkspaceDialog: React.FC = () => {
    const { createWorkspace, creationDialogVisible, setCreationDialogVisible, translate } = useWorkspaces();
    const [isLoading, setIsLoading] = useState(false);

    const handleClose = useCallback(() => {
        setCreationDialogVisible(false);
    }, [setCreationDialogVisible]);

    const handleSubmit = useCallback(
        (formData: FormData) => {
            setIsLoading(true);
            createWorkspace(formData).then(() => {
                setIsLoading(false);
                handleClose();
            });
        },
        [createWorkspace, handleClose]
    );

    if (!creationDialogVisible) return null;

    return (
        <StyledModal isOpen onRequestClose={handleClose} id="createWorkspaceDialog">
            <DialogHeader>{translate('dialog.create.header', 'Create new workspace')}</DialogHeader>
            <WorkspaceForm
                submitLabel={translate('dialog.action.create', 'Create')}
                enabled={!isLoading}
                onSubmit={handleSubmit}
                onCancel={handleClose}
            />
        </StyledModal>
    );
};

export default React.memo(CreateWorkspaceDialog);
