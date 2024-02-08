import * as React from 'react';
import styled from 'styled-components';
import classnames from '../../helper/classnames';

interface IconProps {
    icon: string;
    secondaryIcon: string;
    style?: React.CSSProperties;
    spin?: boolean;
    rotate?: 90 | 180 | 270;
}

const SecondaryIcon = styled.i`
    left: 0.5em;
    top: 0.5em;
    text-shadow: -1px -1px 0px rgba(0, 0, 0, 0.4);
`;

const IconStack: React.FC<IconProps> = ({ icon, secondaryIcon, style, spin = false, rotate = 0 }) => {
    return (
        <span className="fa-stack">
            <i
                className={classnames('fas', 'fa-stack-2x', `fa-${icon}`, rotate && `fa-rotate-${rotate}`)}
                style={style}
            />
            <SecondaryIcon
                className={classnames(
                    'fas',
                    'fa-stack-1x',
                    'fa-inverse',
                    `fa-${secondaryIcon}`,
                    spin && 'fa-spin',
                    rotate && `fa-rotate-${rotate}`
                )}
                style={style}
            />
        </span>
    );
};

export default React.memo(IconStack);
