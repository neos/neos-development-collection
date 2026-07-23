import React, { Component } from "react";
import ImageCropper from "./ImageCropper";
import Variants from "./Variants";
import Original from "./Original";

const calculatePercentageFromPixel = (hundredPercentValue, pixelValue) => {
	if (hundredPercentValue === 0) {
		return 0;
	}
	return Math.min((pixelValue / hundredPercentValue) * 100, 100);
};

const calculatePixelFromPercentage = (hundredPercentValue, percentValue) => {
	return Math.min(
		(percentValue / 100) * hundredPercentValue,
		hundredPercentValue,
	);
};

export default class VariantsApp extends Component {
	constructor(props) {
		const { originalInformation, variantsInformation } = props;
		// technically we don't need the props afterwards, this would be solved by using a state container in the future.
		super(props);

		originalInformation.aspect =
			originalInformation.width / originalInformation.height;

		this.state = {
			originalInformation,
			variantsInformation,
			saving: false,
			error: false,
			crop: false,
			cropConfiguration: {},
			cropVariantPersistenceIdentifier: null,
		};
	}

	requestCrop = (
		cropVariantPersistenceIdentifier,
		aspect,
		x,
		y,
		variantWidth,
		variantHeight,
	) => {
		const { originalInformation } = this.state;

		this.setState({
			crop: true,
			cropVariantPersistenceIdentifier,
			cropConfiguration: {
				unit: "%",
				aspect,
				width: calculatePercentageFromPixel(
					originalInformation.width,
					variantWidth || 0,
				),
				height: calculatePercentageFromPixel(
					originalInformation.height,
					variantHeight || 0,
				),
				x: calculatePercentageFromPixel(originalInformation.width, x || 0),
				y: calculatePercentageFromPixel(originalInformation.height, y || 0),
			},
		});
	};

	closeCrop = () => {
		this.setState({
			error: false,
			crop: false,
			cropVariantPersistenceIdentifier: null,
			cropConfiguration: {},
		});
	};

	changedCrop = (cropConfiguration) =>
		this.setState({
			cropConfiguration,
		});

	saveCrop = () => {
		const {
			originalInformation,
			cropConfiguration,
			cropVariantPersistenceIdentifier,
		} = this.state;
		const form = document.getElementById("postHelper");
		const data = new FormData(form);
		const cropAdjustmentArgument =
			"imageVariant[adjustments][\\Neos\\Media\\Domain\\Model\\Adjustment\\CropImageAdjustment]";
		data.append("imageVariant[__identity]", cropVariantPersistenceIdentifier);
		data.append(
			cropAdjustmentArgument + "[width]",
			calculatePixelFromPercentage(
				originalInformation.width,
				cropConfiguration.width,
			),
		);
		data.append(
			cropAdjustmentArgument + "[height]",
			calculatePixelFromPercentage(
				originalInformation.height,
				cropConfiguration.height,
			),
		);
		data.append(
			cropAdjustmentArgument + "[x]",
			calculatePixelFromPercentage(
				originalInformation.width,
				cropConfiguration.x,
			),
		);
		data.append(
			cropAdjustmentArgument + "[y]",
			calculatePixelFromPercentage(
				originalInformation.height,
				cropConfiguration.y,
			),
		);

		this.setState({ saving: true, error: false });
		fetch(form.getAttribute("action"), {
			method: "POST",
			body: data,
		})
			.then((response) => {
				this.setState({ saving: false });
				if (!response.ok) {
					return Promise.resolve(false);
				}

				return response.json();
			})
			.then((response) => {
				if (!response) {
					this.setState({ error: true });
					return false;
				}

				this.setState({ error: false });
				this.updateVariant(response);
				this.closeCrop();
				return true;
			});
	};

	updateVariant(variantInformation) {
		const { variantsInformation } = this.state;
		const newVariantsInformation = variantsInformation.reduce(function (
			newVariantsInformation,
			item,
		) {
			newVariantsInformation.push(
				item.persistenceIdentifier === variantInformation.persistenceIdentifier
					? variantInformation
					: item,
			);
			return newVariantsInformation;
		}, []);

		this.setState({
			variantsInformation: newVariantsInformation,
		});
	}

	renderCrop() {
		return (
			<div className="neos-modal neos-modal-wide">
				<div className="neos-modal-header">
					<button
						type="button"
						className="neos-close neos-button"
						onClick={this.closeCrop}
					/>
					<div className="neos-header">Crop</div>
				</div>
				<div className="neos-modal-body">
					<ImageCropper
						src={this.state.originalInformation.previewUri}
						keepSelection={true}
						onComplete={this.changedCrop}
						cropConfiguration={this.state.cropConfiguration}
					/>
				</div>
				<div className="neos-modal-footer">
					{this.state.error && (
						<span className="neos-label neos-label-important neos-pull-left">
							An error occured.
						</span>
					)}
					<button
						type="button"
						className="neos-button neos-button-primary"
						disabled={this.state.saving}
						onClick={this.saveCrop}
					>
						Save
					</button>
				</div>
			</div>
		);
	}

	render() {
		const { originalInformation, variantsInformation } = this.state;

		return (
			<div>
				<Original {...originalInformation} />
				<Variants
					variants={variantsInformation}
					onRequestCrop={this.requestCrop}
				/>

				{this.state.crop && this.renderCrop()}
			</div>
		);
	}
}
