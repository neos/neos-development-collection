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
					that.set('_imageFullyLoaded', false);
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
			this.set('_imageFullyLoaded', false);
			this.set('_imageFullyLoaded', true);
			this._updateValue();

			if (SecondaryInspectorController.get('_viewClass') === this.get('_cropView')) {
				// Set empty view for secondary inspector to re-initialize crop view
				SecondaryInspectorController.toggle(Ember.View.extend());
				SecondaryInspectorController.hide();
			}
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

		_imageFullyLoadedDidChange: function() {
			// Apply locked aspect ratio if defined
			if (this.get('_imageFullyLoaded') === true && this.get('crop.aspectRatio.locked.width') > 0) {
				var lockedAspectRatioWidth = this.get('crop.aspectRatio.locked.width'),
					lockedAspectRatioHeight = this.get('crop.aspectRatio.locked.height'),
					lockedAspectRatio = lockedAspectRatioWidth / lockedAspectRatioHeight;
				if (this.get('_cropProperties.aspectRatio') !== lockedAspectRatio) {
					if (lockedAspectRatioWidth > lockedAspectRatioHeight) {
						this.set('_cropProperties.h', parseInt(this.get('_cropProperties.h') / lockedAspectRatio * this.get('_cropProperties.aspectRatio'), 10));
					} else if (lockedAspectRatioWidth === lockedAspectRatioHeight) {
						if (this.get('_cropProperties.w') > this.get('_cropProperties.h')) {
							this.set('_cropProperties.w', parseInt(this.get('_cropProperties.w') * lockedAspectRatio / this.get('_cropProperties.aspectRatio'), 10));
						} else {
							this.set('_cropProperties.h', parseInt(this.get('_cropProperties.h') / lockedAspectRatio * this.get('_cropProperties.aspectRatio'), 10));
						}
					} else {
						this.set('_cropProperties.w', parseInt(this.get('_cropProperties.w') * lockedAspectRatio / this.get('_cropProperties.aspectRatio'), 10));
					}
				}
			}
		}.observes('_imageFullyLoaded'),

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
									width: parent.get('_originalImageSize.w'),
									height: parent.get('_originalImageSize.h')
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
						originalImageWidth = parent.get('_originalImageSize.w'),
						originalImageHeight = parent.get('_originalImageSize.h');
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
							var imageWidthBeforeChange = parent.get('_finalImageScale.w');
							var imageWidthScalingFactor = previewImageCoordinates.w / parent.get('_cropProperties.w');

							Ember.beginPropertyChanges();
							parent.set('_finalImageScale.w', parseInt(imageWidthBeforeChange * imageWidthScalingFactor, 10));

							parent.set('_cropProperties.x', previewImageCoordinates.x);
							parent.set('_cropProperties.y', previewImageCoordinates.y);
							parent.set('_cropProperties.w', previewImageCoordinates.w);
							parent.set('_cropProperties.h', previewImageCoordinates.h);
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
							cropOptions.x + cropOptions.w,
							cropOptions.y + cropOptions.h
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
		 *  Update the preview image when the crop options change or the preview image
		 * is initially loaded. This includes:
		 *
		 * - set the preview bounding box size
		 * - set the preview bounding box offset such that the image is centered
		 * - scale the preview image and set the offsets correctly.
		 */
		_updateCropPreviewImage: function() {
			var that = this;
			// Make sure the image has been updated before altering styles
			Ember.run.next(function() {
				var cropProperties = that.get('_cropProperties.full'),
					container = that.$().find('.neos-inspector-image-thumbnail-inner'),
					image = container.find('img');

				if (that.get('_originalImageUri') && !that.get('_uploadPreviewShown') && (cropProperties.w !== that.get('_previewImageSize.w') || cropProperties.h !== that.get('_previewImageSize.h'))) {
					var scalingFactorX = that.imagePreviewMaximumDimensions.w / cropProperties.w,
						scalingFactorY = that.imagePreviewMaximumDimensions.h / cropProperties.h,
						overallScalingFactor = Math.min(scalingFactorX, scalingFactorY),
						previewBoundingBoxSize = {
							w: Math.floor(cropProperties.w * overallScalingFactor),
							h: Math.floor(cropProperties.h * overallScalingFactor)
						};
					// Update size of preview bounding box and center preview image thumbnail
					container.css({
						width: previewBoundingBoxSize.w + 'px',
						height: previewBoundingBoxSize.h + 'px',
						position: 'absolute',
						left: ((that.imagePreviewMaximumDimensions.w - previewBoundingBoxSize.w) / 2 ) + 'px',
						top: ((that.imagePreviewMaximumDimensions.h - previewBoundingBoxSize.h) / 2) + 'px'
					}).addClass('neos-inspector-image-thumbnail-cropped');

					// Scale Preview image and update relative image position
					image.css({
						width: Math.floor(that.get('_previewImageSize').w * overallScalingFactor) + 'px',
						height:  Math.floor(that.get('_previewImageSize').h * overallScalingFactor) + 'px',
						marginLeft: '-' + (cropProperties.x * overallScalingFactor) + 'px',
						marginTop: '-' + (cropProperties.y * overallScalingFactor) + 'px'
					});
				} else {
					container.attr('style', null).removeClass('neos-inspector-image-thumbnail-cropped');
					image.attr('style', null);
				}
			});
		}.observes('_cropProperties.x', '_cropProperties.y', '_cropProperties.w', '_cropProperties.h', '_originalImageUri', '_uploadPreviewShown'),

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
