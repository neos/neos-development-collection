import styled from 'styled-components';

const ValidationMessage = styled.div`
    color: red;
    font-size: 0.8rem;
    margin-top: 0.5rem;

    & ul {
        padding: 0 1rem;
    }

    & li {
        list-style-type: disc;
    }
`;

export default ValidationMessage;
