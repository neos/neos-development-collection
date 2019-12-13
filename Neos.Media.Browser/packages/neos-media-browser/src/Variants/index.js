import React, {Component} from 'react';
import ImageCropper from './ImageCropper';
import Variants from './Variants';
import Original from './Original';

const calculatePercentageFromPixel = (hundredPercentValue, pixelValue) => {
    return Math.min(((pixelValue / hundredPercentValue) * 100), 100);
};

const calculatePixelFromPercentage = (hundredPercentValue, percentValue) => {
    return Math.min((percentValue / 100) * hundredPercentValue, hundredPercentValue);
};

export default class VariantsApp extends Component {
    constructor(props) {
        const {originalInformation, variantsInformation} = props;
        // technically we don't need the props afterwards, this would be solved by using a state container in the future.
        super(props);

        this.state = {
            originalInformation,
            variantsInformation,
            saving: false,
            error: false,
            crop: false,
            cropConfiguration: {},
            cropVariantPersistenceIdentifier: null,
            cropPendingState: {}
        };
    }

    requestCrop = (variantPersistenceIdentifier, aspectRatio, x, y, width) => {
        const widthPercentage = (width / this.state.originalInformation.width) * 100;
        this.setState({
            crop: true,
            cropVariantPersistenceIdentifier: variantPersistenceIdentifier,
            cropConfiguration: {
                aspect: aspectRatio,
                width: widthPercentage,
                x: x ? calculatePercentageFromPixel(this.state.originalInformation.width, x) : 0,
                y: y ? calculatePercentageFromPixel(this.state.originalInformation.height, y) : 0
            }
        });
    };

    closeCrop = () => {
        this.setState({
            error: false,
            crop: false,
            cropVariantPersistenceIdentifier: {},
            cropPendingState: {},
            cropConfiguration: {}
        })
    };

    changedCrop = (cropPendingState) => this.setState({cropPendingState});

    saveCrop = () => {
        const form = document.getElementById('postHelper');
        const data = new FormData(form);
        const cropAdjustmentArgument = 'imageVariant[adjustments][\\Neos\\Media\\Domain\\Model\\Adjustment\\CropImageAdjustment]';
        data.append('imageVariant[__identity]', this.state.cropVariantPersistenceIdentifier);
        data.append(cropAdjustmentArgument + '[width]', calculatePixelFromPercentage(this.state.originalInformation.width, this.state.cropPendingState.width));
        data.append(cropAdjustmentArgument + '[height]', calculatePixelFromPercentage(this.state.originalInformation.height, this.state.cropPendingState.height));
        data.append(cropAdjustmentArgument + '[x]', calculatePixelFromPercentage(this.state.originalInformation.width, this.state.cropPendingState.x));
        data.append(cropAdjustmentArgument + '[y]', calculatePixelFromPercentage(this.state.originalInformation.height, this.state.cropPendingState.y));

        this.setState({saving: true, error: false});
        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: data,
        }).then((response) => {
            this.setState({saving: false});
            if (!response.ok) {
                return Promise.resolve(false);
            }

            return response.json();
        }).then((response) => {
            if (!response) {
                this.setState({error: true});
                return false;
            }

            this.setState({error: false});
            this.updateVariant(response);
            this.closeCrop();
            return true;
        });
    };

    updateVariant(variantInformation) {
        const {variantsInformation} = this.state;
        const newVariantsInformation = variantsInformation.reduce(function (newVariantsInformation, item) {
            newVariantsInformation.push((item.persistenceIdentifier === variantInformation.persistenceIdentifier) ? variantInformation : item);
            return newVariantsInformation;
        }, []);

        this.setState({
            variantsInformation: newVariantsInformation
        });
    }

    renderCrop() {
        return (
            <div className="neos-modal neos-modal-wide">
                <div className="neos-modal-header">
                    <button type="button" className="neos-close neos-button" onClick={this.closeCrop} />
                    <div className="neos-header">Crop</div>
                </div>
                <div className="neos-modal-body">
                    <ImageCropper src={this.state.originalInformation.previewUri}
                                  keepSelection={true}
                                  onComplete={this.changedCrop}
                                  cropConfiguration={this.state.cropConfiguration}
                    />
                </div>
                <div className="neos-modal-footer">
                    {this.state.error &&
                    <span className="neos-label neos-label-important neos-pull-left">
                        An error occured.
                    </span>
                    }
                    <button type="button" className="neos-button neos-button-primary" disabled={this.state.saving} onClick={this.saveCrop}>Save</button>
                </div>
            </div>
        );
    }

    render() {
        const {originalInformation, variantsInformation} = this.state;

        return (<div>
            <Original {...originalInformation} />
            <Variants variants={variantsInformation} onRequestCrop={this.requestCrop}/>

            {this.state.crop && this.renderCrop()}
        </div>);
    }
}
