import React from 'react';
import Variant from './Variant';

export default class Variants extends React.PureComponent {
    render() {
        const {variants, onRequestCrop} = this.props;
        const renderedVariants = variants.map((variantInformation) => <Variant onRequestCrop={onRequestCrop} key={variantInformation.persistenceIdentifier} {...variantInformation} />);
        return (
            <ul className="neos-thumbnails asset-list">
                {renderedVariants}
            </ul>
        )
    }
}
