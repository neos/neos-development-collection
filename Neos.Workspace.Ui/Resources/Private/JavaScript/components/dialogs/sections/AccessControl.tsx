import React, { ChangeEvent, useCallback, useMemo, useRef, useState } from 'react';

import { useWorkspaces } from '../../../provider/WorkspaceProvider';
import { CheckBoxLabel, FormGroup, Icon, RadioLabel, SearchField } from '../../presentationals';
import styled from 'styled-components';

type SectionProps = {
    workspace?: Workspace;
};

const AclList = styled.div`
    max-height: 7rem;
    margin: 0.5rem 0;
    overflow-x: hidden;
    overflow-y: auto;

    .neos & input[type='checkbox'] {
        margin-top: 0;
    }
`;

const MAX_VISIBLE_USERS = 2;

const AccessControl: React.FC<SectionProps> = ({ workspace }) => {
    const { translate, userList, userCanManageInternalWorkspaces } = useWorkspaces();
    const ownerField = useRef<HTMLSelectElement>(null);
    const [owner, setOwner] = useState(workspace?.owner?.id);
    const [userIDFilter, setUserIDFilter] = useState('');

    const updateOwner = useCallback((event: ChangeEvent<HTMLSelectElement>) => setOwner(event.target.value), []);

    const selectableUserIds = useMemo(() => {
        return Object.keys(userList).filter((userId) => userId && userId !== owner);
    }, [userList, owner]);

    const visibleUserIds = useMemo(() => {
        const filter = userIDFilter.trim().toLowerCase();
        return filter
            ? selectableUserIds.filter((userId) => userList[userId].toLowerCase().indexOf(filter) >= 0)
            : selectableUserIds;
    }, [selectableUserIds, userIDFilter]);

    const showUserFilter = selectableUserIds.length > MAX_VISIBLE_USERS;

    // TODO: Allow setting an owner already during creation
    return workspace ? (
        <>
            <FormGroup>
                {!workspace.isPersonal && (
                    <label>
                        {translate('workspace.owner.label', 'Owner')}
                        <select
                            name={'moduleArguments[workspaceOwner]'}
                            disabled={!userCanManageInternalWorkspaces}
                            defaultValue={workspace?.owner?.id}
                            ref={ownerField}
                            onChange={updateOwner}
                        >
                            {Object.keys(userList).map((userId) => (
                                <option key={userId} value={userId}>
                                    {userList[userId]}
                                </option>
                            ))}
                        </select>
                    </label>
                )}
                <p>
                    <Icon icon="info-circle" style={{ color: 'var(--blue)', marginRight: '.5em' }} />
                    {workspace.isPersonal
                        ? translate('workspace.visibility.isPersonal', 'This workspace is personal')
                        : owner
                        ? translate('workspace.visibility.private.info', 'This workspace is private')
                        : translate('workspace.visibility.internal.info', 'This workspace is internal')}
                </p>
            </FormGroup>
            {!workspace.isPersonal && owner && (
                <FormGroup>
                    <label>{translate('workspace.acl.label', 'Allow access for additional users:')}</label>
                    {showUserFilter && (
                        <SearchField
                            value={userIDFilter}
                            onChange={setUserIDFilter}
                            placeholder={translate('workspace.acl.filter.placeholder', 'Filter users')}
                        />
                    )}
                    <AclList>
                        {selectableUserIds.length > 0
                            ? selectableUserIds.map((userId) => (
                                  <CheckBoxLabel key={userId} data-filtered={!visibleUserIds.includes(userId)}>
                                      <input
                                          type="checkbox"
                                          className="neos-checkbox"
                                          value={userId}
                                          name="moduleArguments[acl][]"
                                          defaultChecked={Object.values(workspace.acl).some(
                                              (user) => user.id === userId
                                          )}
                                      />
                                      {userList[userId]}
                                  </CheckBoxLabel>
                              ))
                            : translate('workspace.acl.filter.empty', 'No users found')}
                    </AclList>
                </FormGroup>
            )}
        </>
    ) : (
        <FormGroup>
            <label className="neos-control-label">{translate('workspace.visibility.label', 'Visibility')}</label>
            {userCanManageInternalWorkspaces && (
                <RadioLabel className="neos-radio">
                    <input type="radio" name="moduleArguments[visibility]" defaultChecked value="internal" />
                    <span />
                    <span>{translate('workspace.visibility.internal', 'Internal')}</span>
                </RadioLabel>
            )}
            <RadioLabel className="neos-radio">
                <input type="radio" name="moduleArguments[visibility]" value="private" />
                <span />
                <span>{translate('workspace.visibility.private', 'Private')}</span>
            </RadioLabel>
        </FormGroup>
    );
};

export default React.memo(AccessControl);
