import styled from 'styled-components';

const CheckBoxLabel = styled.label<{ isHidden: boolean }>`
    flex-direction: row;
    align-items: flex-start;
    gap: 0.6rem;
    cursor: pointer;

    &[data-filtered='true'] {
        display: none;
    }
`;

export default CheckBoxLabel;
