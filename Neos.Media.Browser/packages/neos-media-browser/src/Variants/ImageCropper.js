import React, { PureComponent } from "react";
import ReactCrop from "react-image-crop";
import "react-image-crop/dist/ReactCrop.css";

export default class ImageCropper extends PureComponent {
	handleChange = (_pixelCrop, percentCrop) => {
		const { onComplete } = this.props;
		onComplete(percentCrop);
	};

	handleComplete = (_pixelCrop, percentCrop) => {
		const { onComplete } = this.props;
		onComplete(percentCrop);
	};

	render() {
		const { src, cropConfiguration, keepSelection } = this.props;

		return (
			<ReactCrop
				crop={cropConfiguration}
				onChange={this.handleChange}
				onComplete={this.handleComplete}
				keepSelection={keepSelection}
			>
				<img src={src} alt="" />
			</ReactCrop>
		);
	}
}
