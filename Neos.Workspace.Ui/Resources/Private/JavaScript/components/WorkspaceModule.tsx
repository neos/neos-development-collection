import * as React from 'react';

import WorkspaceTable from './WorkspaceTable';
import { DeleteWorkspaceDialog, EditWorkspaceDialog, CreateWorkspaceDialog } from './dialogs';
import Footer from './Footer';

const WorkspaceModule: React.FC = () => {
    return (
        <>
            <WorkspaceTable />
            <Footer />
            <DeleteWorkspaceDialog />
            <EditWorkspaceDialog />
            <CreateWorkspaceDialog />
        </>
    );
};

export default React.memo(WorkspaceModule);
