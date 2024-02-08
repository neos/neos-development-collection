import * as React from 'react';
import styled from 'styled-components';

import { useWorkspaces } from '../provider/WorkspaceProvider';
import { formatDate } from '../helper/format';
import { ArrowIcon, Badge, Icon, IconStack } from './presentationals';

type WorkspaceTableRowProps = {
    workspaceName: WorkspaceName;
    level: number;
};

const AddedBadge = styled(Badge)`
    background-color: var(--green);
`;

const ChangedBadge = styled(Badge)`
    background-color: var(--warningText);
`;

const DeletedBadge = styled(Badge)`
    background-color: var(--errorText);
`;

const OrphanBadge = styled(Badge)`
    background-color: var(--grayLight);
`;

const Column = styled.td`
    padding: 0 0.5em;
    border-top: 1px solid var(--grayDark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background-color: var(--column-color);
`;

const TextColumn = styled(Column)`
    max-width: 1px;
    width: 25%;
`;

const ActionColumn = styled(Column)`
    padding: 0;
    --column-color: var(--grayMedium);

    & .neos-button[disabled] {
        opacity: 1;
        color: var(--textSubtle);

        &:hover {
            color: var(--textSubtle);
        }
    }
`;

const TypeColumn = styled(Column)`
    text-align: center;

    & > * {
        width: 20px !important;
    }
`;

const Row = styled.tr<{ isUserWorkspace: boolean; isStale: boolean }>`
    --column-color: ${(props) =>
        props.isUserWorkspace ? 'var(--blueDark)' : props.isStale ? 'var(--grayDark)' : 'var(--grayMedium)'};
`;

const InfoText = styled.span`
    font-size: 0.8em;
    font-style: italic;
    margin-left: 0.5em;
    user-select: none;
`;

function getWorkspaceStatusIcon(workspace: Workspace): string {
    if (workspace.isInternal) return 'users';
    if (workspace.acl.length > 0) return 'user-plus';
    return 'user';
}

const WorkspaceTableRow: React.FC<WorkspaceTableRowProps> = ({ workspaceName, level }) => {
    const {
        userWorkspace,
        workspaces,
        setSelectedWorkspaceForEdit,
        setSelectedWorkspaceForDeletion,
        showWorkspace,
        translate,
    } = useWorkspaces();
    const workspace = workspaces[workspaceName];
    const icon = getWorkspaceStatusIcon(workspace);
    const isUserWorkspace = workspaceName === userWorkspace;
    const nodeCountNotCoveredByChanges = workspace.nodeCount - (workspace.changesCounts?.total || 0) - 1;

    return (
        <Row isUserWorkspace={isUserWorkspace} isStale={workspace.isStale} id={`workspace-${workspace.name}`}>
            <TypeColumn
                title={
                    workspace.isStale
                        ? translate('badge.isStale', 'This workspace has not been used for a long time')
                        : workspace.acl.length > 0
                        ? translate(
                              'table.column.access.acl',
                              `This workspace is owned by ${workspace.owner.label} but allows access to additional users`,
                              { owner: workspace.owner.label }
                          )
                        : workspace.owner
                        ? translate(
                              'table.column.access.private',
                              `This workspace is owned by ${workspace.owner.label}`,
                              { owner: workspace.owner.label }
                          )
                        : translate('table.column.access.internal')
                }
            >
                {workspace.isStale ? <IconStack icon={icon} secondaryIcon="clock" /> : <Icon icon={icon} />}
            </TypeColumn>
            <TextColumn title={workspace.name}>
                <span>
                    {workspace.baseWorkspace?.name !== 'live' && (
                        <ArrowIcon style={{ marginLeft: `${0.2 + (level - 1) * 1.2}rem`, marginRight: '0.5rem' }} />
                    )}
                    {workspace.title}
                    {isUserWorkspace && (
                        <InfoText>{translate('badge.isUserWorkspace', 'This is your workspace')}</InfoText>
                    )}
                </span>
            </TextColumn>
            <TextColumn title={workspace.description}>{workspace.description || '–'}</TextColumn>
            <Column>{workspace.creator?.label || '–'}</Column>
            <Column>
                {workspace.lastChangedBy ? workspace.lastChangedBy?.label + ' ' : ''}
                {workspace.lastChangedDate ? formatDate(workspace.lastChangedDate) : '–'}
            </Column>
            <Column>
                {workspace.changesCounts === null ? (
                    <Icon icon="spinner" spin />
                ) : workspace.changesCounts.total > 0 ? (
                    <>
                        {workspace.changesCounts.new > 0 && (
                            <AddedBadge
                                title={translate(
                                    'badge.changes.new',
                                    `${workspace.changesCounts.new} new nodes were added`,
                                    { count: workspace.changesCounts.new }
                                )}
                            >
                                {workspace.changesCounts.new}
                            </AddedBadge>
                        )}
                        {workspace.changesCounts.changed > 0 && (
                            <ChangedBadge
                                title={translate(
                                    'badge.changes.changed',
                                    `${workspace.changesCounts.changed} nodes were changed`,
                                    { count: workspace.changesCounts.changed }
                                )}
                            >
                                {workspace.changesCounts.changed}
                            </ChangedBadge>
                        )}
                        {workspace.changesCounts.removed > 0 && (
                            <DeletedBadge
                                title={translate(
                                    'badge.changes.removed',
                                    `${workspace.changesCounts.removed} nodes were removed`,
                                    { count: workspace.changesCounts.removed }
                                )}
                            >
                                {workspace.changesCounts.removed}
                            </DeletedBadge>
                        )}
                    </>
                ) : nodeCountNotCoveredByChanges > 0 ? (
                    <OrphanBadge
                        title={translate(
                            'badge.changes.unknown',
                            `${nodeCountNotCoveredByChanges} nodes were changed but might be orphaned`,
                            { count: nodeCountNotCoveredByChanges }
                        )}
                    >
                        {nodeCountNotCoveredByChanges}
                    </OrphanBadge>
                ) : isUserWorkspace ? (
                    '–'
                ) : (
                    translate('table.column.changes.empty', 'None')
                )}
            </Column>
            <ActionColumn>
                <button
                    className="neos-button"
                    type="button"
                    title={translate(
                        workspace.changesCounts?.total
                            ? 'table.column.action.show.title'
                            : 'table.column.action.show.disabled.title',
                        `Show changes in workspace ${workspace.title}`,
                        {
                            workspace: workspace.title,
                        }
                    )}
                    disabled={!workspace.changesCounts?.total}
                    onClick={() => showWorkspace(workspaceName)}
                >
                    <Icon icon="review" /> {translate('table.column.action.show', 'Show')}
                </button>
                <button
                    className="neos-button"
                    type="button"
                    title={translate('table.column.action.edit', `Edit workspace ${workspace.title}`, {
                        workspace: workspace.title,
                    })}
                    onClick={() => setSelectedWorkspaceForEdit(workspaceName)}
                    disabled={!workspace.canManage || workspace.changesCounts === null}
                >
                    <Icon icon="pencil-alt" />
                </button>
                <button
                    className="neos-button neos-button-danger"
                    type="button"
                    title={translate('table.column.action.delete', `Delete workspace ${workspace.title}`, {
                        workspace: workspace.title,
                    })}
                    disabled={!workspace.canManage}
                    onClick={() => setSelectedWorkspaceForDeletion(workspaceName)}
                >
                    <Icon icon="trash-alt" />
                </button>
            </ActionColumn>
        </Row>
    );
};

export default React.memo(WorkspaceTableRow);
