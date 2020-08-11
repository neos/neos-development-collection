import React, {PureComponent} from 'react';
import ImageCropper from './ImageCropper';

export default class ImageCropperModal extends PureComponent {
    render() {
        const {src, cropConfiguration, onComplete} = this.props;

        return (
            <div className="neos-modal neos-modal-wide">
                <div className="neos-modal-header">
                    <button type="button" className="neos-close neos-button" onClick={this.closeCrop} />
                    <div className="neos-header">Crop</div>
                </div>
                <div className="neos-modal-body">
                    <ImageCropper src={this.props.originalInformation.previewUri} keepSelection={true}
                                  cropConfiguration={this.state.cropConfiguration}/>
                </div>
                <div className="neos-modal-footer">
                </div>
            </div>
        );
    }
}
