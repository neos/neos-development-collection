import React, { useMemo } from 'react';

import WorkspaceTableRow from './WorkspaceTableRow';
import { SortBy } from './WorkspaceTable';
import { useWorkspaces } from '../provider/WorkspaceProvider';

type WorkspaceTreeNodeProps = {
    workspaceName: WorkspaceName;
    level?: number;
};

const WorkspaceTreeNode: React.FC<WorkspaceTreeNodeProps> = ({ workspaceName, level = -1 }) => {
    const { userWorkspace, sorting, workspaces } = useWorkspaces();

    const sortedDependentWorkspaces = useMemo(() => {
        return Object.values(workspaces)
            .filter(({ baseWorkspace }) => baseWorkspace.name === workspaceName)
            .sort((a, b) => {
                if (a.name === userWorkspace) {
                    return -1;
                } else if (b.name === userWorkspace) {
                    return 1;
                }
                switch (sorting) {
                    case SortBy.title:
                        return a.title.localeCompare(b.title);
                    case SortBy.lastModified:
                        return b.lastChangedTimestamp < a.lastChangedTimestamp ? -1 : 1;
                }
            });
    }, [workspaces, sorting, userWorkspace]);

    return (
        <>
            {workspaceName !== 'live' && <WorkspaceTableRow workspaceName={workspaceName} level={level} />}
            {sortedDependentWorkspaces?.map((dependentWorkspace) => (
                <WorkspaceTreeNode
                    key={dependentWorkspace.name}
                    workspaceName={dependentWorkspace.name}
                    level={level + 1}
                />
            ))}
        </>
    );
};

export default React.memo(WorkspaceTreeNode);
