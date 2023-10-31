import * as React from 'react';
import classnames from '../../helper/classnames';

interface IconProps {
    icon: string;
    style?: React.CSSProperties;
    spin?: boolean;
    rotate?: 90 | 180 | 270;
}

const Icon: React.FC<IconProps> = ({ icon, style, spin = false, rotate = 0 }) => {
    return (
        <i
            className={classnames('fas', `fa-${icon}`, spin && 'fa-spin', rotate && `fa-rotate-${rotate}`)}
            style={style}
        />
    );
};

export default React.memo(Icon);
