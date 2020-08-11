import React from 'react';

export default class Variant extends React.PureComponent {
    render() {
        const {persistenceIdentifier, previewUri, presetIdentifier, width, height, presetVariantName, hasCrop, cropInformation, onRequestCrop} = this.props;
        const aspect = cropInformation.width / cropInformation.height;
        const boundCropHandler = () => onRequestCrop(persistenceIdentifier, aspect, cropInformation.x, cropInformation.y, cropInformation.width);

        return (
            <li className="asset" data-asset-identifier={persistenceIdentifier}
                data-local-asset-identifier={persistenceIdentifier}>
                <a onClick={hasCrop && presetIdentifier && boundCropHandler}>
                    <div className="neos-img-container">
                        <img src={previewUri} className="" alt=""/>
                    </div>
                </a>
                <div className="neos-img-label">
                    {presetIdentifier && (
                        <span className="neos-caption asset-label">
                            <span className="neos-badge neos-pull-right">
                                <pre>W: {width}px <br/>H: {height}px</pre>
                            </span>
                            <div className="neos-alert neos-alert-block">{presetIdentifier}: {presetVariantName}</div>
                        </span>
                    )}
                </div>
            </li>
        );
    }
}
