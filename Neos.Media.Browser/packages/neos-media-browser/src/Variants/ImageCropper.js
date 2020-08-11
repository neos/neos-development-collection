import React, {PureComponent} from 'react';
import ReactCrop from 'react-image-crop';
import './react_crop.vanilla-css';

export default class ImageCropper extends PureComponent {
    render() {
        const {src, cropConfiguration, onComplete} = this.props;

        return (<ReactCrop
            src={src}
            crop={cropConfiguration}
            onComplete={onComplete}
            onAspectRatioChange={onComplete}
            onImageLoaded={onComplete}
        />);
    }
}
