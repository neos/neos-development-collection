import React from 'react';
import styled from 'styled-components';
import ReactModal from 'react-modal';

interface ModalProps extends ReactModal.Props {
    className?: string;
    modalClassName?: string;
}

const ReactModalAdapter: React.FC<ModalProps> = ({ className, modalClassName, ...props }: ModalProps) => {
    return <ReactModal className={modalClassName} portalClassName={className} {...props} />;
};

export const StyledModal = styled(ReactModalAdapter).attrs({
    overlayClassName: 'Overlay',
    modalClassName: 'Modal',
})`
    .Overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(1px);
        z-index: 110; // higher than module footer
        display: flex;
        justify-content: center;
        align-items: center;

        & strong {
            font-weight: bold;
        }
    }

    .Modal {
        border: 1px solid var(--grayDarker);
        background: var(--grayDark);
        overflow: auto;
        --webkit-overflow-scrolling: touch;
        border-radius: 4px;
        outline: none;
        padding: 20px;
    }
`;

export const ActionBar = styled.div`
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
`;

export const DialogHeader = styled.h2`
    margin-bottom: 1rem;
`;
