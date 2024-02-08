import React, { ChangeEvent, useCallback, useMemo, useRef, useState } from 'react';
import styled from 'styled-components';

import { useWorkspaces } from '../../provider/WorkspaceProvider';
import { CheckBoxLabel, RadioLabel, ValidationMessage } from '../presentationals';
import { ActionBar } from './StyledModal';
import BaseWorkspaceSelection from './sections/BaseWorkspaceSelection';
import AccessControl from './sections/AccessControl';

const Form = styled.form`
    width: 400px;
    max-width: 100%;

    & label {
        display: flex;
        margin-bottom: 0.5rem;
        flex-direction: column;
    }

    & ${RadioLabel}, & ${CheckBoxLabel} {
        flex-direction: row;
    }

    .neos.neos-module & input[type='text'],
    .neos.neos-module & input[type='search'],
    .neos.neos-module & select {
        display: block;
        width: 100%;
        margin-top: 0.3rem;
    }

    .neos.neos-module & select {
        height: auto;
        min-height: var(--spacing-GoldenUnit);

        & option {
            padding: 3px 0;
            margin: 3px 0;

            &::before {
                content: ' ';
                display: inline-block;
                margin-right: 0.3rem;
                width: 1em;
                text-align: center;
            }

            &:checked {
                background-color: transparent;
                color: var(--textOnGray);

                &::before {
                    content: '✓';
                }
            }
        }
    }
`;

type FormProps = {
    enabled: boolean;
    onSubmit: (formData: FormData) => void;
    onCancel: () => void;
    submitLabel: string;
    workspace?: Workspace;
};

const WorkspaceForm: React.FC<FormProps> = ({ enabled, onSubmit, onCancel, submitLabel, workspace }) => {
    const { csrfToken, translate, validation } = useWorkspaces();
    const workspaceForm = useRef<HTMLFormElement>(null);
    const [title, setTitle] = useState(workspace?.title ? workspace.title : '');

    const argumentPrefix = 'moduleArguments';

    const updateTitle = useCallback((event: ChangeEvent<HTMLInputElement>) => {
        if (event.target.value) {
            setTitle(event.target.value);
        }
    }, []);

    const titleIsValid = useMemo(() => {
        const regex = new RegExp(validation.titlePattern);
        return regex.test(title);
    }, [title]);

    const handleSubmit = useCallback(() => {
        onSubmit(new FormData(workspaceForm.current));
    }, [workspaceForm.current]);

    return (
        <Form ref={workspaceForm}>
            <input type="hidden" name={'__csrfToken'} value={csrfToken} />
            {workspace && <input type="hidden" name={`${argumentPrefix}[workspaceName]`} value={workspace.name} />}
            <label>
                {translate('workspace.title.label', 'Title')}
                <input
                    type="text"
                    name={`${argumentPrefix}[title]`}
                    maxLength={200}
                    required
                    onChange={updateTitle}
                    defaultValue={workspace?.title || ''}
                />
                {title && !titleIsValid && (
                    <ValidationMessage dangerouslySetInnerHTML={{ __html: translate('workspace.title.validation') }} />
                )}
            </label>
            <label>
                {translate('workspace.description.label', 'Description')}
                <input
                    type="text"
                    name={`${argumentPrefix}[description]`}
                    defaultValue={workspace?.description || ''}
                    maxLength={500}
                />
            </label>
            {!workspace && <BaseWorkspaceSelection workspace={workspace} />}
            <AccessControl workspace={workspace} />
            <ActionBar>
                <button type="button" className="neos-button" onClick={onCancel}>
                    {translate('dialog.action.cancel', 'Cancel')}
                </button>
                <button
                    type="button"
                    id="createWorkspaceDialogSubmit"
                    className="neos-button neos-button-primary"
                    onClick={handleSubmit}
                    disabled={!enabled || !titleIsValid}
                >
                    {submitLabel}
                </button>
            </ActionBar>
        </Form>
    );
};

export default React.memo(WorkspaceForm);
