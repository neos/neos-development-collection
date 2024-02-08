import styled from 'styled-components';

/**
 * This is a generic badge and should be extended via styled components
 */
const Badge = styled.span`
    background-color: var(--grayLight);
    border-radius: 15%;
    color: var(--textOnGray);
    padding: 0.2em 0.5em;
    width: 33%;
    user-select: none;
    cursor: help;

    & + * {
        margin-left: 0.5em;
    }
`;

export default Badge;
