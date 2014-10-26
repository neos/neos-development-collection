define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Content/Inspector/Editors/FileUpload',
	'text!./ImageEditor.html',
	'Content/Inspector/Editors/BooleanEditor',
	'Library/spinjs/spin',
	'Content/Inspector/SecondaryInspectorController',
	'Shared/Notification',
	'Shared/Utility',
	'Shared/HttpClient'
],
function(Ember, $, FileUpload, template, BooleanEditor, Spinner, SecondaryInspectorController, Notification, Utility, HttpClient) {
	/**
	 * The Image has to extend from fileUpload; as plupload just breaks with very weird
	 * error messages otherwise.
	 */
	return FileUpload.extend({
		/****************************************
		 * GENERAL SETUP
		 ***************************************/
		fileChooserLabel: 'Choose Image',

		uploaderLabel: 'Upload!',
		removeButtonLabel: 'Remove',
		uploadCancelLabel: 'Cancel',

		/**
		 * Size of the image preview. Public configuration.
		 */
		imagePreviewMaximumDimensions: {w: 288, h: 216},

		/**
		 * Comma-separated list of allowed file types.
		 * Public configuration.
		 */
		allowedFileTypes: 'jpg,jpeg,png,gif',

		template: Ember.Handlebars.compile(template),
		BooleanEditor: BooleanEditor,
		SecondaryInspectorButton: SecondaryInspectorController.SecondaryInspectorButton,

		/**
		 * The Upload Preview is the image being shown *before* the user presses
		 * "upload".
		 */
		_uploadPreviewShown: true,
		_uploadPreviewNotShown: function() {
			return !this.get('_uploadPreviewShown');
		}.property('_uploadPreviewShown'),

		_uploadPreviewImageSource: '',
		_defaultUploadPreviewImageSource: $('link[rel="neos-public-resources"]').attr('href') + 'Images/dummy-image.svg',

		/**
		 * @var boolean
		 * set to true as soon as the image is fully loaded and all image properties
		 * are initialized. Is set when selecting an image or uploading a new one.
		 */
		_imageFullyLoaded: false,

		/****************************************
		 * IMAGE SIZING
		 ***************************************/
		/**
		 * This is the UUID of the full-size image; which is being stored.
		 */
		_originalImageUuid: null,

		/**
		 * The "original" image is shown in the sidebar.
		 */
		_originalImageUri: null,

		/**
		 * size of the original (base) image ("w" + "h")
		 */
		_originalImageSize: null,

		/**
		 * The "preview" image is used for cropping.
		 */
		_previewImageUri: null,

		/**
		 * This is the size of the image being used for cropping
		 * Object "w" + "h"
		 */
		_previewImageSize: null,

		/**
		 * Crop properties, as being used by jCrop editor.
		 * ALL COORDINATES are relative to _previewImageSize.
		 */
		_cropProperties: null,

		/**
		 * After cropping, we still scale the cropped image
		 */
		_finalImageScale: null,

		/**
		 * Contains the handler for the AJAX request loading the preview image
		 */
		_loadPreviewImageHandler: null,

		/**
		 * The url of the image service endpoint used for fetching image metadata
		 */
		_imageServiceEndpointUri: null,

		loadingindicator: null,

		init: function() {
			var that = this;
			this._super();

			this.set('_imageServiceEndpointUri', $('link[rel="neos-images"]').attr('href'));

			this.set('_uploadPreviewImageSource', this.get('_defaultUploadPreviewImageSource'));

			this.set('_finalImageScale', Ember.Object.extend({
				w: null,
				h: null,

				_updateValueIfWidthAndHeightChange: function() {
					that._updateValue();
				}.observes('w', 'h'),

				/**
				 * As width and height are always connected through the aspect ratio,
				 * we cannot directly change the height. Instead, we need to adjust the
				 * width as needed and let the bindings update the height property instead.
				 *
				 * As a result, the input field for the image height is bound to
				 * "computedHeight", and not just to "h".
				 */
				computedHeight: function(key, value) {
					if (arguments.length === 1) {
						return this.get('h');
					} else {
						this.set('w', Math.ceil(that.get('_cropProperties.aspectRatio') * value));
						return value;
					}
				}.property('h')
			}).create());

			this.set('_cropProperties',  Ember.Object.extend({
				x: null,
				y: null,
				w: null,
				h: null,

				full: function() {
					return {
						x: this.get('x'),
						y: this.get('y'),
						w: this.get('w'),
						h: this.get('h')
					};
				}.property('w', 'h', 'x', 'y'),

				initialized: function() {
					return this.get('w') !== null && this.get('h') !== null && this.get('x') !== null && this.get('y') !== null;
				}.property('w', 'h', 'x', 'y'),

				aspectRatio: function() {
					if (isNaN(this.get('w'))
						|| isNaN(this.get('h'))
						|| this.get('w') === 0
						|| this.get('h') === 0) {
						return 1;
					}

					return parseFloat(this.get('w')) / parseFloat(this.get('h'));
				}.property('w', 'h')
			}).create());
		},

		_imageWidthToggle: function(propertyName, value) {
			if (typeof value === 'boolean') {
				if (value === false) {
					this.set('_finalImageScale.w', null);
					this.set('_finalImageScale.h', null);
				} else {
					this.set('_finalImageScale.w', this.get('_originalImageSize.w'));
					this.set('_finalImageScale.h', this.get('_originalImageSize.h'));
				}
			}
			if (this.get('_finalImageScale.w') > 0) {
				return true;
			}
			this.set('_finalImageScale.w', null);
			this.set('_finalImageScale.h', null);
			return false;
		}.property('_finalImageScale.w'),

		_imageHeightToggle: function(propertyName, value) {
			if (typeof value === 'boolean') {
				if (value === false) {
					this.set('_finalImageScale.w', null);
					this.set('_finalImageScale.h', null);
				} else {
					this.set('_finalImageScale.w', this.get('_originalImageSize.w'));
					this.set('_finalImageScale.h', this.get('_originalImageSize.h'));
				}
			}
			if (this.get('_finalImageScale.w') > 0) {
				return true;
			}
			this.set('_finalImageScale.w', null);
			this.set('_finalImageScale.h', null);
			return false;
		}.property('_finalImageScale.h'),

		_aspectRatioChanged: function() {
			this.set('_finalImageScale.h', parseInt(this.get('_finalImageScale.w') / this.get('_cropProperties.aspectRatio'), 10));
		}.observes('_finalImageScale.w', '_cropProperties.aspectRatio'),

		/****************************************
		 * INITIALIZATION
		 ***************************************/
		/**
		 * Lifecycle callback; sets some CSS for the image preview area to sensible defaults,
		 * and reads the image if possible
		 */
		didInsertElement: function() {
			var that = this;

			this._super();
			this.$().find('.neos-inspector-image-thumbnail').click(function() {
				if (!that.get('_uploadPreviewShown')) {
					SecondaryInspectorController.toggle(that.get('_cropView'));
				}
			});

			this._readAndDeserializeValue();
		},

		willDestroyElement: function() {
				// Hide popover when the focus changes
			if (this.get('_loadPreviewImageHandler')) {
				this.get('_loadPreviewImageHandler').abort();
			}
		},

		/****************************************
		 * MEDIA BROWSER
		 ***************************************/
		_mediaBrowserView: Ember.View.extend({
			template: Ember.Handlebars.compile('<iframe style="width:100%; height: 100%" src="' + $('link[rel="neos-image-browser"]').attr('href') + '"></iframe>')
		}),

		_beforeMediaBrowserIsShown: function() {
			var that = this;
			window.Typo3MediaBrowserCallbacks = {
				assetChosen: function(assetIdentifier) {
					that._displayImageLoader();

					// we hide the default upload preview image; as we only want the loading indicator to be visible
					that.set('_uploadPreviewShown', false);
					that.set('_loadPreviewImageHandler', HttpClient.getResource(
						that.get('_imageServiceEndpointUri') + '?image=' + assetIdentifier,
						{dataType: 'json'}
					));
					that.get('_loadPreviewImageHandler').then(function(result) {
						that.fileUploaded(result);
						that._hideImageLoader();
					});
					that.set('mediaBrowserShown', false);
				}
			};
		},

		/****************************************
		 * IMAGE REMOVE
		 ***************************************/
		remove: function() {
			this.set('_originalImageUuid', null);
			this.set('_originalImageUri', null);
			this.set('_previewImageUri', null);
			this.set('_finalImageScale.w', null);
			this.set('_finalImageScale.h', null);
			this.set('_uploadPreviewImageSource', this.get('_defaultUploadPreviewImageSource'));
			this.set('_uploadPreviewShown', true);
			this.set('value', '');
		},

		cancel: function() {
			if (this.get('value') !== '') {
				this._readAndDeserializeValue();
			} else {
				this.set('_uploadPreviewImageSource', this.get('_defaultUploadPreviewImageSource'));
			}
			this.set('_uploadPreviewShown', true);
			this.set('_uploadInProgress', false);
			this.set('_uploadButtonShown', false);
		},

		/****************************************
		 * IMAGE UPLOAD
		 ***************************************/
		/**
		 * Display before-upload Image preview
		 */
		filesScheduledForUpload: function(files) {
			var that = this;
			if (files.length > 0) {
				var image = files[0];

				if (typeof window.FileReader === 'function') {
					var reader = new FileReader();
					reader.onload = function(event) {
						var binaryData = event.target.result;
						that.set('_uploadPreviewImageSource', binaryData);

						var imageObjForFindingSize = new window.Image();
						imageObjForFindingSize.onload = function() {
							that.set('_uploadPreviewShown', true);
						};
						imageObjForFindingSize.src = binaryData;
					};

					reader.readAsDataURL(image);
				}
			} else {
				that.set('_uploadPreviewShown', false);
				that.set('_uploadButtonShown', false);
			}
		},

		/**
		 * Callback after file upload is complete
		 */
		fileUploaded: function(response) {
			// if used directly as callback for in filesScheduledForUpload and not in the media
			// browser via getJSON() a string will be handed over.
			if (typeof response === 'string') {
				response = JSON.parse(response);
			}
			if (response === null) {
				Notification.error('Tried to fetch image metadata: Unexpected result format.');
				return;
			}
			this._super();

			this.set('_originalImageUuid', response.imageUuid);
			this._setPreviewImage(response);

				// We only need to set the width here; as the height is automatically
				// calculated from the aspect ratio in the cropper
			this.set('_finalImageScale.w', response.originalSize.w);

			this._resetCropPropertiesToCurrentPreviewImageSize();
			this.set('_imageFullyLoaded', true);
			this._updateValue();
		},

		_setPreviewImage: function(responseJson) {
			Ember.beginPropertyChanges();

			this.set('_originalImageSize', responseJson.originalSize);
			this.set('_originalImageUri', responseJson.originalImageResourceUri);
			this.set('_previewImageSize', responseJson.previewSize);
			this.set('_previewImageUri', responseJson.previewImageResourceUri);
			this.set('_uploadPreviewShown', false);

			Ember.endPropertyChanges();
		},

		_resetCropPropertiesToCurrentPreviewImageSize: function() {
			this.set('_cropProperties.x', 0);
			this.set('_cropProperties.y', 0);
			this.set('_cropProperties.w', this.get('_previewImageSize.w'));
			this.set('_cropProperties.h', this.get('_previewImageSize.h'));
		},

		/****************************************
		 * CROPPING
		 ***************************************/
		_cropView: function() {
			var that = this;

			return Ember.View.extend({
				classNames: ['neos-secondary-inspector-image-crop'],

				template: Ember.Handlebars.compile('<img />'),
				didInsertElement: function() {
					var $image = this.$().find('img');
					$image.attr('src', that.get('_previewImageUri'));

					var settings = {
							// Triggered when the selection is finished
						onSelect: function(previewImageCoordinates) {

							// Besides updating the crop dimensions (_cropProperties.*), we also update
							// the *width* of the final image, to make sure that if we select only a part
							// of the final image, the image is not up-scaled but keeps the same resolution.
							//
							// NOTE: The height of the image is auto-calculated, so we only need to set the width.
							var imageWidthBeforeChange = that.get('_finalImageScale.w');
							var imageWidthScalingFactor = previewImageCoordinates.w / that.get('_cropProperties.w');

							Ember.beginPropertyChanges();
							that.set('_finalImageScale.w', parseInt(imageWidthBeforeChange * imageWidthScalingFactor, 10));

							that.set('_cropProperties.x', previewImageCoordinates.x);
							that.set('_cropProperties.y', previewImageCoordinates.y);
							that.set('_cropProperties.w', previewImageCoordinates.w);
							that.set('_cropProperties.h', previewImageCoordinates.h);
							Ember.endPropertyChanges();
							that._updateValue();
						}
					};

						// If we have all crop options set, we preselect this in the cropping tool.
					if (that.get('_cropProperties.initialized')) {
						var cropOptions = that.get('_cropProperties.full');

						settings.setSelect = [
							cropOptions.x,
							cropOptions.y,
							cropOptions.x + cropOptions.w,
							cropOptions.y + cropOptions.h
						];
					}
					$image.Jcrop(settings);
				}
			});
		}.property(),

		/**
		 *  Update the preview image when the crop options change or the preview image
		 * is initially loaded. This includes:
		 *
		 * - set the preview bounding box size
		 * - set the preview bounding box offset such that the image is centered
		 * - scale the preview image and sete the offsets correctly.
		 */
		_updateCropPreviewImage: function() {
			if (!this.get('_previewImageUri')) {
				return;
			}

			var cropProperties = this.get('_cropProperties.full'),
				container = this.$().find('.neos-inspector-image-thumbnail-inner'),
				image = container.find('img');

			if (!this.get('_uploadPreviewShown') && (cropProperties.w !== this.get('_previewImageSize.w') || cropProperties.h !== this.get('_previewImageSize.h'))) {
				var scalingFactorX = this.imagePreviewMaximumDimensions.w / cropProperties.w,
					scalingFactorY = this.imagePreviewMaximumDimensions.h / cropProperties.h,
					overallScalingFactor = Math.min(scalingFactorX, scalingFactorY),
					previewBoundingBoxSize = {
						w: Math.floor(cropProperties.w * overallScalingFactor),
						h: Math.floor(cropProperties.h * overallScalingFactor)
					};
					// Update size of preview bounding box
					// and Center preview image thumbnail
				container.css({
					width: previewBoundingBoxSize.w + 'px',
					height: previewBoundingBoxSize.h + 'px',
					position: 'absolute',
					left: ((this.imagePreviewMaximumDimensions.w - previewBoundingBoxSize.w) / 2 ) + 'px',
					top: ((this.imagePreviewMaximumDimensions.h - previewBoundingBoxSize.h) / 2) + 'px'
				}).addClass('neos-inspector-image-thumbnail-cropped');;

					// Scale Preview image and update relative image position
				image.css({
					width: Math.floor(this.get('_previewImageSize').w * overallScalingFactor) + 'px',
					height:  Math.floor(this.get('_previewImageSize').h * overallScalingFactor) + 'px',
					marginLeft: '-' + (cropProperties.x * overallScalingFactor) + 'px',
					marginTop: '-' + (cropProperties.y * overallScalingFactor) + 'px'
				});
			} else {
				container.attr('style', null).removeClass('neos-inspector-image-thumbnail-cropped');
				image.attr('style', null);
			}
		}.observes('_cropProperties.x', '_cropProperties.y', '_cropProperties.w', '_cropProperties.h', '_previewImageUri', '_uploadPreviewShown'),

		/****************************************
		 * Saving / Loading
		 ***************************************/
		/**
		 * This function must be triggered *explicitly* when either:
		 * _originalImageUuid, _cropProperties or _finalImageScale are modified, as it
		 * writes these changes back into a JSON object.
		 *
		 * We don't use value observing here, as this might end up with a circular
		 * dependency.
		 */
		_updateValue: function() {
			if (!this.get('_cropProperties.initialized') || !this.get('_imageFullyLoaded')) {
				return;
			}
			// Prevent the user from setting width and height to empty

			var originalImageCropDimensions = this._convertCropOptionsFromPreviewImageCoordinates(this.get('_cropProperties.full')),
				width = this.get('_finalImageScale.w'),
				height = this.get('_finalImageScale.h'),
				processingInstructions = [{
					command: 'crop',
					options: {
						start: {
							x: originalImageCropDimensions.x,
							y: originalImageCropDimensions.y
						},
						size: {
							width: originalImageCropDimensions.w,
							height: originalImageCropDimensions.h
						}
					}
				}];

			if (width > 0 && height > 0) {
				processingInstructions.push({
					command: 'resize',
					options: {
						size: {
							width: width,
							height: height
						}
					}
				});
			}

			this.set('value', JSON.stringify({
				originalImage: this.get('_originalImageUuid'),
				processingInstructions: processingInstructions
			}));
		},

		/**
		 * On startup, we deserialize the JSON string and fill _originalImageUuid, _scaleOptions and _cropOptions
		 */
		_readAndDeserializeValue: function() {
			var that = this,
				imageVariant = this.get('value');

			if (!imageVariant || !Utility.isValidJsonString(imageVariant)) {
				return;
			}
			try {
				imageVariant = JSON.parse(imageVariant);
			} catch(e) {
					// In case we do not have valid JSON here, let's silently return
				return;
			}
			if (imageVariant) {
					// We now load the metadata for the image variant, and as soon as we have it fully initialize the
					// widget.
				this._displayImageLoader();

					// we hide the default upload preview image; as we only want the loading indicator to be visible
				this.set('_uploadPreviewShown', false);
				that.set('_loadPreviewImageHandler', HttpClient.getResource(
					$('link[rel="neos-images"]').attr('href') + '?image=' + imageVariant.originalImage,
					{dataType: 'json'}
				));
				that.get('_loadPreviewImageHandler').then(function(metadata) {
					that._hideImageLoader();
					that.beginPropertyChanges();
					that._setPreviewImage(metadata);
					that.set('_originalImageUuid', imageVariant.originalImage);

					$.each(imageVariant.processingInstructions, function(index, instruction) {
						if (instruction.command === 'crop') {
							var finalSizeCropProperties = {
								x: Ember.get(instruction, 'options.start.x'),
								y: Ember.get(instruction, 'options.start.y'),
								w: Ember.get(instruction, 'options.size.width'),
								h: Ember.get(instruction, 'options.size.height')
							};

							var previewImageCropProperties = that._convertCropOptionsToPreviewImageCoordinates(finalSizeCropProperties);

							that.set('_cropProperties.x', previewImageCropProperties.x);
							that.set('_cropProperties.y', previewImageCropProperties.y);
							that.set('_cropProperties.w', previewImageCropProperties.w);
							that.set('_cropProperties.h', previewImageCropProperties.h);
						} else if (instruction.command === 'resize') {
							that.set('_finalImageScale.w', Ember.get(instruction, 'options.size.width'));
								// Height does not need to be set, as it is automatically calculated from crop properties + width
						}
					});

					that.endPropertyChanges();
					that.set('_imageFullyLoaded', true);
				});
			}
		},

		_displayImageLoader: function() {
			if (this.loadingindicator !== null) {
				this.loadingindicator.spin(this.$().find('.neos-inspector-image-thumbnail').get(0));
				return;
			}
			this.loadingindicator = new Spinner({
				lines: 13, // The number of lines to draw
				length: 15, // The length of each line
				width: 4, // The line thickness
				radius: 10, // The radius of the inner circle
				corners: 1, // Corner roundness (0..1)
				rotate: 0, // The rotation offset
				color: '#fff', // #rgb or #rrggbb
				speed: 1, // Rounds per second
				trail: 64, // Afterglow percentage
				shadow: false, // Whether to render a shadow
				hwaccel: false, // Whether to use hardware acceleration
				className: 'spinner', // The CSS class to assign to the spinner
				zIndex: 2e9, // The z-index (defaults to 2000000000)
				top: 'auto', // Top position relative to parent in px
				left: 'auto' // Left position relative to parent in px
			}).spin(this.$().find('.neos-inspector-image-thumbnail').get(0));
		},

		_hideImageLoader: function() {
			this.loadingindicator.stop();
		},

		/**
		 * Helper.
		 *
		 * Convert the crop options from the *preview image* coordinate system to the
		 * *master image* coordinate system which is stored persistently.
		 *
		 * The inverse function to this method is _convertCropOptionsToPreviewImageCoordinates
		 */
		_convertCropOptionsFromPreviewImageCoordinates: function(previewImageCoordinates) {
			var previewImageSize = this.get('_previewImageSize'),
				originalImageSize = this.get('_originalImageSize');

			return {
				x: previewImageCoordinates.x * (originalImageSize.w / previewImageSize.w),
				y: previewImageCoordinates.y * (originalImageSize.h / previewImageSize.h),
				w: previewImageCoordinates.w * (originalImageSize.w / previewImageSize.w),
				h: previewImageCoordinates.h * (originalImageSize.h / previewImageSize.h)
			};
		},

		/**
		 * Helper.
		 *
		 * Convert the crop options from the *master image* coordinate system to the
		 * *preview image* coordinate system. We need this as the *preview image* used as
		 * basis for cropping might be smaller than the original one.
		 *
		 * The inverse function to this method is _convertCropOptionsFromPreviewImageCoordinates
		 */
		_convertCropOptionsToPreviewImageCoordinates: function(coordinates) {
			var previewImageSize = this.get('_previewImageSize'),
				originalImageSize = this.get('_originalImageSize');

			return {
				x: parseInt(coordinates.x / (originalImageSize.w / previewImageSize.w), 10),
				y: parseInt(coordinates.y / (originalImageSize.h / previewImageSize.h), 10),
				w: parseInt(coordinates.w / (originalImageSize.w / previewImageSize.w), 10),
				h: parseInt(coordinates.h / (originalImageSize.h / previewImageSize.h), 10)
			};
		},

		_initializeUploader: function() {
			this._super();

			this._uploader.bind('BeforeUpload', function(uploader, file) {
				uploader.settings.multipart_params['metadata'] = 'Image';
			});
		}
	});
});
