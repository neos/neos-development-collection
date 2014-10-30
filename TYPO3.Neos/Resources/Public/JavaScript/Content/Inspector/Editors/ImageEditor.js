define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Content/Inspector/Editors/FileUpload',
	'text!./ImageEditor.html',
	'text!./ImageEditorCrop.html',
	'Content/Inspector/Editors/BooleanEditor',
	'Library/spinjs/spin',
	'Content/Inspector/SecondaryInspectorController',
	'Shared/Notification',
	'Shared/Utility',
	'Shared/HttpClient'
],
function(Ember, $, FileUpload, template, cropTemplate, BooleanEditor, Spinner, SecondaryInspectorController, Notification, Utility, HttpClient) {
	/**
	 * The Image has to extend from fileUpload; as plupload just breaks with very weird
	 * error messages otherwise.
	 */
	return FileUpload.extend({

		/****************************************
		 * GENERAL SETUP
		 ***************************************/
		fileChooserLabel: 'Choose',

		uploaderLabel: 'Upload!',
		removeButtonLabel: 'Remove',
		uploadCancelLabel: 'Cancel',
		cropLabel: 'Crop',

		/**
		 * Size of the image preview. Public configuration.
		 */
		imagePreviewMaximumDimensions: {width: 288, height: 216},

		/**
		 * Comma-separated list of allowed file types.
		 * Public configuration.
		 */
		allowedFileTypes: 'jpg,jpeg,png,gif',

		/**
		 * Feature flags for this editor. Currently we have cropping and resize which can be enabled/disabled via NodeTypes editorOptions.
		 */
		features: {
			crop: false,
			resize: false
		},

		template: Ember.Handlebars.compile(template),
		BooleanEditor: BooleanEditor,
		SecondaryInspectorButton: SecondaryInspectorController.SecondaryInspectorButton,

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
		 * This is the original object data used to update.
		 */
		_object: null,

		/**
		 * The "original" image is shown in the sidebar.
		 */
		_originalImageUri: null,

		/**
		 * size of the original (base) image ("w" + "h")
		 */
		_originalImageDimensions: null,

		/**
		 * Current image adjustments
		 */
		_adjustments: {},

		/**
		 * The "preview" image is used for cropping.
		 */
		_previewImageUri: null,

		/**
		 * This is the size of the image being used for cropping
		 * Object "w" + "h"
		 */
		_previewImageDimensions: null,

		/**
		 * Crop properties, as being used by jCrop editor.
		 * ALL COORDINATES are relative to _previewImageDimensions.
		 */
		_cropProperties: null,

		/**
		 * Object representing the dimensions of the final image.
		 *
		 * As width and height are always connected through the aspect ratio, we cannot directly change
		 * the height. Instead, we need to adjust the width as needed and let the bindings update the height
		 * property instead.
		 *
		 * As a result, the input field for the image height is bound to "computedHeight", and not just
		 * to "height".
		 */
		_finalImageDimensions: null,

		/**
		 * Contains the handler for the AJAX request loading the preview image
		 */
		_loadPreviewImageHandler: null,

		/**
		 * The url of the image service endpoint used for fetching image metadata
		 */
		_imageServiceEndpointUri: null,

		loadingIndicator: null,

		init: function() {
			var that = this;
			this._super();

			this.set('_imageServiceEndpointUri', $('link[rel="neos-images"]').attr('href'));

			this.set('_finalImageDimensions', Ember.Object.extend({
				width: null,
				height: null,

				_updateValueIfWidthAndHeightChange: function() {
					that._updateValue();
				}.observes('width', 'height'),

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
						return this.get('height');
					} else {
						this.set('width', Math.ceil(that.get('_cropProperties.aspectRatio') * value));
						return value;
					}
				}.property('height')
			}).create());

			/**
			 * Object representing the parameters for a potential cropping operation
			 */
			this.set('_cropProperties',  Ember.Object.extend({
				x: null,
				y: null,
				width: null,
				height: null,

				full: function() {
					return {
						height: Math.ceil(this.get('height')),
						width: Math.ceil(this.get('width')),
						x: this.get('x'),
						y: this.get('y')
					};
				}.property('width', 'height', 'x', 'y'),

				initialized: function() {
					return this.get('width') !== null && this.get('height') !== null && this.get('x') !== null && this.get('y') !== null;
				}.property('width', 'height', 'x', 'y'),

				aspectRatio: function() {
					var ratio = 1,
						width = this.get('width'),
						height = this.get('height');
					if (width > 0 && height > 0) {
						ratio = parseFloat(width) / parseFloat(height);
					}

					return ratio;
				}.property('width', 'height')
			}).create());
		},

		/**
		 * Logic for the checkbox next to the image width text field
		 */
		_imageWidthToggle: function(propertyName, value) {
			if (typeof value === 'boolean') {
				if (value === false) {
					this.set('_finalImageDimensions.width', null);
					this.set('_finalImageDimensions.height', null);
				} else {
					this.set('_finalImageDimensions.width', this.get('_originalImageDimensions.width'));
					this.set('_finalImageDimensions.height', this.get('_originalImageDimensions.height'));
				}
			}
			if (this.get('_finalImageDimensions.width') > 0) {
				return true;
			}
			this.set('_finalImageDimensions.width', null);
			this.set('_finalImageDimensions.height', null);
			return false;
		}.property('_finalImageDimensions.width'),

		/**
		 * Logic for the checkbox next to the image height text field
		 */
		_imageHeightToggle: function(propertyName, value) {
			if (typeof value === 'boolean') {
				if (value === false) {
					this.set('_finalImageDimensions.width', null);
					this.set('_finalImageDimensions.height', null);
				} else {
					this.set('_finalImageDimensions.width', this.get('_originalImageDimensions.width'));
					this.set('_finalImageDimensions.height', this.get('_originalImageDimensions.height'));
				}
			}
			if (this.get('_finalImageDimensions.width') > 0) {
				return true;
			}
			this.set('_finalImageDimensions.width', null);
			this.set('_finalImageDimensions.height', null);
			return false;
		}.property('_finalImageDimensions.height'),

		/**
		 * If the aspect ratio changes, adjust the height based on the width and the aspect ratio
		 */
		_aspectRatioChanged: function() {
			this.set('_finalImageDimensions.height', parseInt(this.get('_finalImageDimensions.width') / this.get('_cropProperties.aspectRatio'), 10));
		}.observes('_finalImageDimensions.width', '_cropProperties.aspectRatio'),

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

			this.$().find('.neos-inspector-image-thumbnail-inner').css({
				width: this.get('imagePreviewMaximumDimensions.width') + 'px',
				height: this.get('imagePreviewMaximumDimensions.height') + 'px'
			});
			this.$().find('.neos-inspector-image-thumbnail-container').css({
				width: this.get('imagePreviewMaximumDimensions.width') + 'px',
				height: this.get('imagePreviewMaximumDimensions.height') + 'px'
			});

			this.$().find('.neos-inspector-image-thumbnail').click(function() {
				SecondaryInspectorController.toggle(that.get('_cropView'));
			});

			this._readAndDeserializeValue();
		},

		/**
		 * Hide the popover as soon as the image editor looses focus
		 */
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
			this.set('_object', null);
			this.set('_originalImageUri', null);
			this.set('_previewImageUri', null);
			this.set('_finalImageDimensions.width', null);
			this.set('_finalImageDimensions.height', null);
			this.set('value', '');
		},

		cancel: function() {
			if (this.get('value') !== '') {
				this._readAndDeserializeValue();
			}
			this.set('_uploadInProgress', false);
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
				this._displayImageLoader();
				this.upload();
			}
		},

		/**
		 * Callback after file upload is complete
		 */
		fileUploaded: function(response) {
			var metadata;
			this._hideImageLoader();
			// if used directly as callback for in filesScheduledForUpload and not in the media
			// browser via getJSON() a string will be handed over.
			if (typeof response === 'string') {
				metadata = JSON.parse(response);
			} else {
				metadata = response;
			}
			if (response === null) {
				Notification.error('Tried to fetch image metadata: Unexpected result format.');
				return;
			}
			this._super();
			Ember.beginPropertyChanges();
			this._applyLoadedMetadata(metadata);
			this._resetCropPropertiesToCurrentPreviewImageDimensions();
			// We only need to set the width here; as the height is automatically
			// calculated from the aspect ratio in the cropper
			this.set('_finalImageDimensions.width', metadata.originalDimensions.width);
			Ember.endPropertyChanges();
			this.set('_imageFullyLoaded', true);
			this._updateValue();
		},

		_resetCropPropertiesToCurrentPreviewImageDimensions: function() {
			this.set('_cropProperties.width', this.get('_previewImageDimensions.width'));
			this.set('_cropProperties.height', this.get('_previewImageDimensions.height'));
			this.set('_cropProperties.x', 0);
			this.set('_cropProperties.y', 0);
		},

		/****************************************
		 * CROPPING
		 ***************************************/

		_cropView: function() {
			var parent = this;

			return Ember.View.extend({
				classNames: ['neos-secondary-inspector-image-crop'],
				template: Ember.Handlebars.compile(cropTemplate),
				aspectRatioWidth: null,
				aspectRatioHeight: null,
				aspectRatioReducedNumerator: 0,
				aspectRatioReducedDenominator: 0,
				aspectRatioAllowCustom: false,
				aspectRatioLocked: false,
				aspectRatioOptions: [],
				api: null,
				selection: null,

				init: function() {
					this._super();
					var that = this,
						configuration = parent.get('crop');
					if (configuration.aspectRatio) {
						if (configuration.aspectRatio.locked && configuration.aspectRatio.locked.width > 0 && configuration.aspectRatio.locked.height > 0) {
							this.set('aspectRatioWidth', configuration.aspectRatio.locked.width);
							this.set('aspectRatioHeight', configuration.aspectRatio.locked.height);
							this.set('aspectRatioLocked', true);
						} else {
							var options = Ember.A(),
								option = Ember.Object.extend({
									init: function() {
										this._super();
										var reducedRatio = that.reduceRatio(this.get('width'), this.get('height'));
										this.set('reducedNominator', reducedRatio[0]);
										this.set('reducedDenominator', reducedRatio[1]);
									},
									label: function() {
										return this.get('reducedNominator') + ':' + this.get('reducedDenominator');
									}.property()
								});
							// Add empty option to allow deselect
							options.push(option.create({label: ''}));
							if (configuration.aspectRatio.enableOriginal !== false) {
								options.push(option.create({
									key: 'original',
									label: 'Original',
									width: parent.get('_originalImageDimensions.width'),
									height: parent.get('_originalImageDimensions.height')
								}));
							}
							if (configuration.aspectRatio.allowCustom !== false) {
								that.set('aspectRatioAllowCustom', true);
								options.push(option.create({
									key: 'custom',
									label: 'Custom'
								}));
							}
							for (var key in configuration.aspectRatio.options) {
								if (configuration.aspectRatio.options.hasOwnProperty(key) && configuration.aspectRatio.options[key]) {
									options.push(option.create($.extend(configuration.aspectRatio.options[key], {key: key})));
								}
							}
							this.set('aspectRatioOptions', options);
						}
					}
				},

				_selectionDidChange: function() {
					var option = this.get('aspectRatioOptions').findBy('key', this.get('selection'));
					if (!option) {
						return;
					}
					clearTimeout(this.get('customTimeout'));
					if (option.get('key') !== 'custom') {
						this.set('aspectRatioWidth', option.width);
						this.set('aspectRatioHeight', option.height);
					} else {
						var that = this;
						// Use timeout since selection is changed multiple times
						this.set('customTimeout', setTimeout(function() {
							if (that.$().find('input[type="number"]:focus').length === 0) {
								that.$().find('.neos-image-editor-crop-aspect-ratio input[type="number"]:first').focus();
							}
						}, 50));
					}
				}.observes('selection'),

				aspectRatioReduced: function() {
					var aspectRatioReducedNumerator = this.get('aspectRatioReducedNumerator'),
						aspectRatioReducedDenominator = this.get('aspectRatioReducedDenominator');
					return aspectRatioReducedNumerator ? aspectRatioReducedNumerator + ':' + aspectRatioReducedDenominator : '';
				}.property('aspectRatioReducedNumerator', 'aspectRatioReducedDenominator'),

				originalAspectRatio: function() {
					var aspectRatioWidth = this.get('aspectRatioWidth'),
						aspectRatioHeight = this.get('aspectRatioHeight'),
						originalImageWidth = parent.get('_originalImageDimensions.width'),
						originalImageHeight = parent.get('_originalImageDimensions.height');
					return aspectRatioWidth / aspectRatioHeight === originalImageWidth / originalImageHeight;
				}.property('aspectRatioWidth', 'aspectRatioHeight'),

				selectedAspectRatioOption: function() {
					var aspectRatioWidth = this.get('aspectRatioWidth'),
						aspectRatioHeight = this.get('aspectRatioHeight'),
						aspectRatio = aspectRatioWidth / aspectRatioHeight,
						matchesOption = false,
						options = this.get('aspectRatioOptions');
					options.forEach(function(option) {
						if (option.width / option.height === aspectRatio) {
							option.set('active', true);
							matchesOption = true;
						} else {
							option.set('active', false);
						}
					});
					if (this.get('aspectRatioAllowCustom') && !this.get('originalAspectRatio') && !matchesOption && aspectRatioWidth > 0 && aspectRatioHeight > 0) {
						options.findBy('label', 'Custom').set('active', true);
					}
					var activeOption = options.findBy('active', true);
					if (activeOption) {
						this.set('selection', activeOption.get('key'));
					} else {
						this.set('selection', null);
					}
					var that = this;
					Ember.run.next(function() {
						that.$().find('select').trigger('change');
					});
				}.observes('aspectRatioWidth', 'aspectRatioHeight').on('init'),

				_aspectRatioDidChange: function() {
					var aspectRatioWidth = this.get('aspectRatioWidth'),
						aspectRatioHeight = this.get('aspectRatioHeight'),
						api = this.get('api');
					if (api) {
						api.setOptions({aspectRatio: aspectRatioWidth > 0 && aspectRatioHeight > 0 ? aspectRatioWidth / aspectRatioHeight : 0});
					}
					var reducedRatio;
					if (aspectRatioWidth && aspectRatioHeight) {
						reducedRatio = this.reduceRatio(aspectRatioWidth, aspectRatioHeight);
					}
					this.set('aspectRatioReducedNumerator', reducedRatio ? reducedRatio[0] : null);
					this.set('aspectRatioReducedDenominator', reducedRatio ? reducedRatio[1] : null);
				}.observes('aspectRatioWidth', 'aspectRatioHeight').on('init'),

				actions: {
					exchangeAspectRatio: function() {
						var aspectRatioWidth = this.get('aspectRatioWidth'),
							aspectRatioHeight = this.get('aspectRatioHeight');
						this.setProperties({'aspectRatioWidth': aspectRatioHeight, 'aspectRatioHeight': aspectRatioWidth});
					}
				},

				/**
				 * Reduce a numerator and denominator to it's smallest, integer ratio using Euclid's Algorithm
				 */
				reduceRatio: function(numerator, denominator) {
					var temp,
						divisor,
						isInteger = function(n) {
							return n % 1 === 0;
						},
						greatestCommonDivisor = function(a, b) {
							if (b === 0) {
								return a;
							}
							return greatestCommonDivisor(b, a % b);
						};

					if (numerator === denominator) {
						return [1, 1];
					}

					// make sure numerator is always the larger number
					if (+numerator < +denominator) {
						temp = numerator;
						numerator = denominator;
						denominator = temp;
					}

					divisor = greatestCommonDivisor(+numerator, +denominator);

					if (typeof temp === 'undefined') {
						return [numerator / divisor, denominator / divisor];
					} else {
						return [denominator / divisor, numerator / divisor];
					}
				},

				didInsertElement: function() {
					var $image = this.$().find('img');
					$image.attr('src', parent.get('_previewImageUri'));

					var update = function(previewImageCoordinates) {
							// Besides updating the crop dimensions (_cropProperties.*), we also update
							// the *width* of the final image, to make sure that if we select only a part
							// of the final image, the image is not up-scaled but keeps the same resolution.
							//
							// NOTE: The height of the image is auto-calculated, so we only need to set the width.
							var imageWidthBeforeChange = parent.get('_finalImageDimensions.width');
							var imageWidthScalingFactor = previewImageCoordinates.width / parent.get('_cropProperties.width');

							Ember.beginPropertyChanges();
							parent.set('_finalImageDimensions.width', parseInt(imageWidthBeforeChange * imageWidthScalingFactor, 10));

							parent.set('_cropProperties.width', previewImageCoordinates.w);
							parent.set('_cropProperties.height', previewImageCoordinates.h);
							parent.set('_cropProperties.x', previewImageCoordinates.x);
							parent.set('_cropProperties.y', previewImageCoordinates.y);
							Ember.endPropertyChanges();
							parent._updateValue();
						},
						settings = {
							// Triggered when the selection is finished or updated
							onSelect: update,
							onChange: update
						};

					// If we have all crop options set, we preselect this in the cropping tool.
					if (parent.get('_cropProperties.initialized')) {
						var cropOptions = parent.get('_cropProperties.full');

						settings.setSelect = [
							cropOptions.x,
							cropOptions.y,
							cropOptions.x + cropOptions.width,
							cropOptions.y + cropOptions.height
						];
					}

					if (this.get('aspectRatioLocked')) {
						settings.aspectRatio = this.get('aspectRatioWidth') / this.get('aspectRatioHeight');
					}

					var that = this;
					$image.Jcrop(settings, function() {
						that.set('api', this);
					});

					this.$().find('select').select2({
						maximumSelectionSize: 1,
						minimumResultsForSearch: 10,
						allowClear: true,
						placeholder: 'Aspect ratio',
						dropdownCssClass: 'neos-select2-large'
					});
				}
			});
		}.property(),


		/**
		 * Update the preview image when the crop options change or the preview image
		 * is initially loaded. This includes:
		 *
		 * - set the preview bounding box size
		 * - set the preview bounding box offset such that the image is centered
		 * - scale the preview image and set the offsets correctly
		 */
		_updateCropPreviewImage: function() {
			if (!this.get('_previewImageUri')) {
				return;
			}

			var cropProperties = this.get('_cropProperties.full'),
				container = this.$().find('.neos-inspector-image-thumbnail-inner'),
				image = container.find('img');

			if (cropProperties.width !== this.get('_previewImageDimensions.width') || cropProperties.height !== this.get('_previewImageDimensions.height')) {
				var scalingFactorX = this.imagePreviewMaximumDimensions.width / cropProperties.width,
					scalingFactorY = this.imagePreviewMaximumDimensions.height / cropProperties.height,
					overallScalingFactor = Math.min(scalingFactorX, scalingFactorY),
					previewBoundingBoxDimensions = {
						width: Math.floor(cropProperties.width * overallScalingFactor),
						height: Math.floor(cropProperties.height * overallScalingFactor)
					};
					// Update size of preview bounding box
					// and Center preview image thumbnail
				container.css({
					width: previewBoundingBoxDimensions.width + 'px',
					height: previewBoundingBoxDimensions.height + 'px',
					position: 'absolute',
					left: ((this.imagePreviewMaximumDimensions.width - previewBoundingBoxDimensions.width) / 2 ) + 'px',
					top: ((this.imagePreviewMaximumDimensions.height - previewBoundingBoxDimensions.height) / 2) + 'px'
				}).addClass('neos-inspector-image-thumbnail-cropped');

					// Scale Preview image and update relative image position
				image.css({
					width: Math.floor(this.get('_previewImageDimensions').width * overallScalingFactor) + 'px',
					height:  Math.floor(this.get('_previewImageDimensions').height * overallScalingFactor) + 'px',
					marginLeft: '-' + (cropProperties.x * overallScalingFactor) + 'px',
					marginTop: '-' + (cropProperties.y * overallScalingFactor) + 'px'
				});
			} else {
				container.attr('style', null).removeClass('neos-inspector-image-thumbnail-cropped');
				image.attr('style', null);
			}
		}.observes('_cropProperties.x', '_cropProperties.y', '_cropProperties.width', '_cropProperties.height', '_object'),

		/****************************************
		 * Saving / Loading
		 ***************************************/

		/**
		 * This function must be triggered *explicitly* when either* _originalImage, _cropProperties or
		 * _finalImageDimensions are modified, as it writes these changes back into a JSON object.
		 *
		 * We don't use value observing here, as this might end up with a circular dependency.
		 */
		_updateValue: function() {
			if (!this.get('_cropProperties.initialized') || !this.get('_imageFullyLoaded')) {
				return;
			}

			var value = this.get('_object');

			this._applyEditorChangesToAdjustments();
			if (Object.keys(this.get('_adjustments')).length > 0) {
				// we need to transform this into an ImageVariant
				if (value.__type === 'TYPO3\\Media\\Domain\\Model\\Image') {
					value = {
						__type: 'TYPO3\\Media\\Domain\\Model\\ImageVariant',
						originalAsset: value.__identity
					};
				}
				value.adjustments = this.get('_adjustments');
			}

			this.set('value', JSON.stringify(value));
		},

		/**
		 * On startup, we deserialize the JSON string found in the editor's "value" property and fill _originalImage,
		 * _scaleOptions and _cropOptions with their respective values.
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
				// We now load more detailed data  for the image variant, and as soon as we have it fully initialize
				// the widget.
				this._displayImageLoader();

				that.set('_loadPreviewImageHandler', HttpClient.getResource(
					that.get('_imageServiceEndpointUri') + '?image=' + imageVariant.__identity,
					{dataType: 'json'}
				));
				that.get('_loadPreviewImageHandler').then(function(metadata) {
					that._hideImageLoader();
					that.beginPropertyChanges();
					that._applyLoadedMetadata(metadata);
					that._updateAdjustmentsFromObject();
					that._resetCropPropertiesToCurrentPreviewImageDimensions();
					that.endPropertyChanges();
					that.set('_imageFullyLoaded', true);
					that._updateValue();
				});
			}
		},

		_applyLoadedMetadata: function(metadata) {
			this.set('_object', metadata.object);
			this.set('_originalImageDimensions', metadata.originalDimensions);
			this.set('_originalImageUri', metadata.originalImageResourceUri);
			this.set('_previewImageDimensions', metadata.previewDimensions);
			this.set('_previewImageUri', metadata.previewImageResourceUri);
		},

		_applyEditorChangesToAdjustments: function() {
			// Prevent the user from setting width and height to empty
			var finalWidth = this.get('_finalImageDimensions.width'),
				finalHeight = this.get('_finalImageDimensions.height'),
				originalWidth = this.get('_originalImageDimensions.width'),
				originalHeight = this.get('_originalImageDimensions.height'),
				cropProperties = this._convertCropOptionsFromPreviewImageCoordinates(this.get('_cropProperties.full'));

			if ((finalWidth > 0 && finalHeight > 0) && (this['_adjustments']['TYPO3\\Media\\Domain\\Model\\Adjustment\\ResizeImageAdjustment'] || (finalWidth != originalWidth || finalHeight != originalHeight))) {
				this._applyResizeAdjustment(finalWidth, finalHeight);
			}

			if (this['_adjustments']['TYPO3\\Media\\Domain\\Model\\Adjustment\\CropImageAdjustment'] || (cropProperties.x != 0 && cropProperties.y != 0 && cropProperties.width != originalWidth && cropProperties.height != originalHeight)) {
				this._applyCropAdjustment(cropProperties);
			}
		},

		_applyResizeAdjustment: function(finalWidth, finalHeight) {
			this['_adjustments']['TYPO3\\Media\\Domain\\Model\\Adjustment\\ResizeImageAdjustment'] = {
				height: finalHeight,
				maximumHeight: null,
				maximumWidth: null,
				minimumHeight: null,
				minimumWidth: null,
				ratioMode: null,
				width: finalWidth
			};
		},

		_applyCropAdjustment: function(cropProperties) {
			this['_adjustments']['TYPO3\\Media\\Domain\\Model\\Adjustment\\CropImageAdjustment'] = cropProperties;
		},

		/**
		 *
		 */
		_updateAdjustmentsFromObject: function () {
			var that = this,
				adjustments = {};
			if (this.get('_object.adjustments')) {
				adjustments = this.get('_object.adjustments');
				$.each(adjustments, function (index, adjustment) {
					if (index === 'TYPO3\\Media\\Domain\\Model\\Adjustment\\CropImageAdjustment') {
						var finalSizeCropProperties = {
							height: Ember.get(adjustment, 'height'),
							width: Ember.get(adjustment, 'width'),
							x: Ember.get(adjustment, 'x'),
							y: Ember.get(adjustment, 'y')
						};

						var previewImageCropProperties = that._convertCropOptionsToPreviewImageCoordinates(finalSizeCropProperties);

						that.set('_cropProperties.height', previewImageCropProperties.height);
						that.set('_cropProperties.width', previewImageCropProperties.width);
						that.set('_cropProperties.x', previewImageCropProperties.x);
						that.set('_cropProperties.y', previewImageCropProperties.y);
					}
					if (index === 'TYPO3\\Media\\Domain\\Model\\Adjustment\\ResizeImageAdjustment') {
						that.set('_finalImageDimensions.width', Ember.get(adjustment, 'width'));
						// Height does not need to be set, as it is automatically calculated from crop properties + width
					}
				});
			}

			this.set('_adjustments', adjustments);
		}.observes('_object'),

		/**
		 * Helper
		 *
		 * Convert the crop options from the *preview image* coordinate system to the
		 * *master image* coordinate system which is stored persistently.
		 *
		 * The inverse function to this method is _convertCropOptionsToPreviewImageCoordinates
		 */
		_convertCropOptionsFromPreviewImageCoordinates: function(previewImageCoordinates) {
			var previewImageDimensions = this.get('_previewImageDimensions'),
				originalImageDimensions = this.get('_originalImageDimensions');

			return {
				height: Math.round(previewImageCoordinates.height * (originalImageDimensions.height / previewImageDimensions.height)),
				width: Math.round(previewImageCoordinates.width * (originalImageDimensions.width / previewImageDimensions.width)),
				x: Math.round(previewImageCoordinates.x * (originalImageDimensions.width / previewImageDimensions.width)),
				y: Math.round(previewImageCoordinates.y * (originalImageDimensions.height / previewImageDimensions.height))
			};
		},

		/**
		 * Helper
		 *
		 * Convert the crop options from the *master image* coordinate system to the
		 * *preview image* coordinate system. We need this as the *preview image* used as
		 * basis for cropping might be smaller than the original one.
		 *
		 * The inverse function to this method is _convertCropOptionsFromPreviewImageCoordinates
		 */
		_convertCropOptionsToPreviewImageCoordinates: function(coordinates) {
			var previewImageDimensions = this.get('_previewImageDimensions'),
				originalImageDimensions = this.get('_originalImageDimensions');

			return {
				height: Math.round(coordinates.height / (originalImageDimensions.height / previewImageDimensions.height), 10),
				width: Math.round(coordinates.width / (originalImageDimensions.width / previewImageDimensions.width), 10),
				x: Math.round(coordinates.x / (originalImageDimensions.width / previewImageDimensions.width), 10),
				y: Math.round(coordinates.y / (originalImageDimensions.height / previewImageDimensions.height), 10)
			};
		},

		_initializeUploader: function() {
			this._super();

			this._uploader.bind('BeforeUpload', function(uploader, file) {
				uploader.settings.multipart_params.metadata = 'Image';
			});

			var that = this;
			this._uploader.bind('Error', function(uploader, error) {
				that.cancel();
			});
		},

		/**
		 * Image Loader
		 */
		_displayImageLoader: function () {
			if (this.loadingIndicator !== null) {
				this.loadingIndicator.spin(this.$().find('.neos-inspector-image-thumbnail').get(0));
				return;
			}
			this.loadingIndicator = new Spinner({
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

		_hideImageLoader: function () {
			this.loadingIndicator.stop();
		}
	});
});
