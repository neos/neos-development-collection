import React, { useCallback } from 'react';
import styled from 'styled-components';

import WorkspaceTreeNode from './WorkspaceTreeNode';
import { useWorkspaces } from '../provider/WorkspaceProvider';
import { Icon } from './presentationals';

const Table = styled.table`
    margin-top: 1em;
    border-spacing: 0;
    position: relative;
    width: 100%;
`;

const HeaderColumn = styled.th`
    padding: 0.5em;
    position: sticky;
    top: 40px;
    user-select: none;
    background: var(--grayDark);
    border-bottom: 1px solid var(--grayDark);
    z-index: 1;
`;

const IconButton = styled.button`
    background: none;
    border: none;
    padding: 0;
    margin: 0;
    cursor: pointer;
    outline: none;
    color: var(--textOnGray);
`;

export enum SortBy {
    title,
    lastModified,
}

const WorkspaceTable: React.FC = () => {
    const { sorting, setSorting, translate } = useWorkspaces();

    const handleSortByTitle = useCallback(() => {
        setSorting(SortBy.title);
    }, []);

    const handleSortByLastModified = useCallback(() => {
        setSorting(SortBy.lastModified);
    }, []);

    return (
        <Table id="workspaceTable">
            <thead>
                <tr>
                    <HeaderColumn> </HeaderColumn>
                    <HeaderColumn>
                        <IconButton
                            type="button"
                            onClick={handleSortByTitle}
                            style={sorting === SortBy.title ? { color: 'var(--blue)' } : {}}
                        >
                            {translate('table.header.title', 'Title')} <Icon icon="sort-alpha-down" />
                        </IconButton>
                    </HeaderColumn>
                    <HeaderColumn>{translate('table.header.description', 'Description')}</HeaderColumn>
                    <HeaderColumn>{translate('table.header.creator', 'Creator')}</HeaderColumn>
                    <HeaderColumn>
                        <IconButton
                            type="button"
                            onClick={handleSortByLastModified}
                            style={sorting === SortBy.lastModified ? { color: 'var(--blue)' } : {}}
                        >
                            {translate('table.header.lastModified', 'Last modified')} <Icon icon="sort" />
                        </IconButton>
                    </HeaderColumn>
                    <HeaderColumn>{translate('table.header.changes', 'Changes')}</HeaderColumn>
                    <HeaderColumn>{translate('table.header.actions', 'Actions')}</HeaderColumn>
                </tr>
            </thead>
            <tbody>
                <WorkspaceTreeNode workspaceName="live" />
            </tbody>
        </Table>
    );
};

export default React.memo(WorkspaceTable);
