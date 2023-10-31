import React, { useMemo } from 'react';
import { useWorkspaces } from '../provider/WorkspaceProvider';
import styled from 'styled-components';

const WorkspaceCount = styled.div`
    display: inline-block;
    color: var(--textSubtleLight);
    font-size: var(--generalFontSize);
    line-height: var(--unit);
    vertical-align: middle;
    margin-left: var(--spacing-Full);
`;

const Footer: React.FC = () => {
    const { setCreationDialogVisible, workspaces, translate } = useWorkspaces();

    const workspaceCount = useMemo(() => {
        return Object.values(workspaces).reduce(
            (counts, workspace) => {
                counts.total++;
                if (workspace.isInternal) {
                    counts.internal++;
                } else {
                    counts.private++;
                }
                return counts;
            },
            {
                total: 0,
                internal: 0,
                private: 0,
            }
        );
    }, [workspaces]);

    return (
        <div className="neos-footer">
            <button
                id="createButton"
                type="button"
                className="neos-button neos-button-success"
                onClick={() => setCreationDialogVisible(true)}
            >
                {translate('footer.action.create', 'Create new workspace')}
            </button>
            <WorkspaceCount>
                {translate(
                    'footer.workspaceCount',
                    `${workspaceCount.total} workspaces (${workspaceCount.internal} public, ${workspaceCount.private} private)`,
                    workspaceCount
                )}
            </WorkspaceCount>
        </div>
    );
};

export default React.memo(Footer);
