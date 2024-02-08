import React from 'react';
import { Icon } from './index';
import styled from 'styled-components';

type Props = {
    onChange: (value: string) => void;
    value: string;
    placeholder: string;
};

const SearchFieldWrap = styled.div`
    position: relative;

    & button {
        display: none;
    }

    & input:not([value='']) + button {
        display: block;
        position: absolute;
        top: 0;
        right: 0;
        height: 100%;
        color: var(--grayLighter);
        background: none;
        border: none;
        padding: 0 0.8rem;
    }

    & input:focus + button {
        color: var(--textOnWhite);
    }
`;

const SearchField: React.FC<Props> = ({ onChange, value, placeholder }) => {
    return (
        <SearchFieldWrap>
            <input type="search" placeholder={placeholder} onChange={(e) => onChange(e.target.value)} value={value} />
            <button type="button" onClick={() => onChange('')}>
                <Icon icon="times" />
            </button>
        </SearchFieldWrap>
    );
};

export default React.memo(SearchField);
