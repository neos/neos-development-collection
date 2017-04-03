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
	'Shared/HttpClient',
	'Shared/I18n'
],
function (Ember, $, FileUpload, template, cropTemplate, BooleanEditor, Spinner, SecondaryInspectorController, Notification, Utility, HttpClient, I18n) {
	/**
	 * The Image has to extend from fileUpload; as plupload just breaks with very weird
	 * error messages otherwise.
	 */
	return FileUpload.extend({

		/****************************************
		 * GENERAL SETUP
		 ***************************************/
		removeButtonLabel: function () {
			return I18n.translate('Neos.Neos:Main:remove', 'Remove');
		}.property(),
		uploadCancelLabel: function () {
			return I18n.translate('Neos.Neos:Main:cancel', 'Cancel');
		}.property(),
		cropLabel: function () {
			return I18n.translate('Neos.Neos:Main:crop', 'Crop');
		}.property(),
		mediaLabel: function () {
			return I18n.translate('Neos.Neos:Main:media', 'Media');
		}.property(),

		/**
		 * Size of the image preview. Public configuration.
		 */
		imagePreviewMaximumDimensions: {width: 288, height: 216},

		/**
		 * Comma-separated list of allowed file types.
		 * Public configuration.
		 * TODO: Should probably be generated server-side depending on the configured imagine driver.
		 */
		allowedFileTypes: 'jpg,jpeg,png,gif,svg',

		/**
		 * Feature flags for this editor. Currently we have cropping and resize which can be enabled/disabled via NodeTypes editorOptions.
		 */
		features: {
			crop: true,
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

		/**
		 * The url of the endpoint able to create new ImageVariants
		 */
		_createImageVariantEndpointUri: null,

		loadingIndicator: null,

		init: function () {
			var that = this;
			this._super();

			// Create new instance per image editor to avoid side effects
			this._initializeMediaView();

			this.set('_imageServiceEndpointUri', $('link[rel="neos-images"]').attr('href'));
			this.set('_createImageVariantEndpointUri', $('link[rel="neos-imagevariant-create"]').attr('href'));

			this.set('_finalImageDimensions', Ember.Object.extend({
				width: null,
				height: null,

				_updateValueIfWidthAndHeightChange: function () {
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
				computedHeight: function (key, value) {
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
			this.set('_cropProperties', Ember.Object.extend({
				x: null,
				y: null,
				width: null,
				height: null,

				full: function () {
					return {
						x: this.get('x'),
						y: this.get('y'),
						width: Math.ceil(this.get('width')),
						height: Math.ceil(this.get('height'))
					};
				}.property('width', 'height', 'x', 'y'),

				initialized: function () {
					return this.get('width') !== null && this.get('height') !== null && this.get('x') !== null && this.get('y') !== null;
				}.property('width', 'height', 'x', 'y'),

				aspectRatio: function () {
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
		_imageWidthToggle: function (propertyName, value) {
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
		_imageHeightToggle: function (propertyName, value) {
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
			var finalImageWidth = this.get('_finalImageDimensions.width');
			if (finalImageWidth !== null) {
				this.set('_finalImageDimensions.height', parseInt(finalImageWidth / this.get('_cropProperties.aspectRatio'), 10));
			}
		}.observes('_finalImageDimensions.width', '_cropProperties.aspectRatio'),

		/****************************************
		 * INITIALIZATION
		 ***************************************/

		/**
		 * Lifecycle callback; sets some CSS for the image preview area to sensible defaults,
		 * and reads the image if possible
		 */
		didInsertElement: function () {
			this._super();

			this.$().find('.neos-inspector-image-thumbnail-inner').css({
				width: this.get('imagePreviewMaximumDimensions.width') + 'px',
				height: this.get('imagePreviewMaximumDimensions.height') + 'px'
			});
			this.$().find('.neos-inspector-image-thumbnail-container').css({
				width: this.get('imagePreviewMaximumDimensions.width') + 'px',
				height: this.get('imagePreviewMaximumDimensions.height') + 'px'
			});
			this._readAndDeserializeValue();
		},

		/**
		 * Hide the popover as soon as the image editor looses focus
		 */
		willDestroyElement: function () {
			// Hide popover when the focus changes
			if (this.get('_loadPreviewImageHandler')) {
				this.get('_loadPreviewImageHandler').abort();
			}
		},

		/****************************************
		 * MEDIA BROWSER
		 ***************************************/
		_mediaBrowserView: null,

		_beforeMediaBrowserIsShown: function () {
			var that = this,
				value = null;

			try {
				if (this.get('value')) {
					value = JSON.parse(this.get('value'));
				}
			} catch (exception) {
				console.log('Invalid JSON value in image editor', this.get('value'));
			}

			window.NeosMediaBrowserCallbacks = {
				_assetIdentifier: value && '__identity' in value ? value.__identity : null,
				_frameLoaded: false,
				_reloadPreviewImage: function() {
					if (this._assetIdentifier) {
						var originalPreviewImageResourceUri = that.get('_previewImageUri');

						that._displayImageLoader();

						that.set('_loadPreviewImageHandler', HttpClient.getResource(
							that.get('_imageServiceEndpointUri') + '?image=' + this._assetIdentifier,
							{dataType: 'json'}
						));
						that.get('_loadPreviewImageHandler').then(function (result) {
							if (originalPreviewImageResourceUri !== result.previewImageResourceUri) {
								that.fileUploaded(result);
							}
							that._hideImageLoader();
						});
					}
				},
				onLoad: function(event, iframe) {
					this._frameLoaded = true;
					var notifications = $(iframe).contents().find('#neos-notifications-inline');
					if (notifications.length > 0) {
						$('li', notifications).each(function(index, notification) {
							var title = $(notification).data('title');
							Notification[$(notification).data('type')](title ? title : $(notification).text(), title ? $(notification).html() : '');
						});
					}
				},
				refreshThumbnail: function() {
					if (this._frameLoaded) {
						this._reloadPreviewImage();
					}
				},
				assetChosen: function(assetIdentifier) {
					if (assetIdentifier) {
						this._assetIdentifier = assetIdentifier;
						this._reloadPreviewImage();
					}
					that.set('mediaBrowserShown', false);
				},
				close: function() {
					that.set('mediaBrowserShown', false);
				}
			};
		},

		_mediaBrowserEditView: null,

		_initializeMediaBrowserEditView: function () {
			this.set('_mediaBrowserEditView', Ember.View.extend({
				template: Ember.Handlebars.compile('<iframe style="width:100%; height: 100%" src="' + $('link[rel="neos-image-browser-edit"]').attr('href') + '?asset[__identity]=' + this.get("_object").__identity + '"></iframe>'),
				didInsertElement: function() {
					this.$().find('iframe').on('load', function(event) {
						if (window.NeosMediaBrowserCallbacks && window.NeosMediaBrowserCallbacks.onLoad) {
							window.NeosMediaBrowserCallbacks.onLoad(event, this);
						}
					});
				}
			}));
		},

		_beforeMediaBrowserEditIsShown: function () {
			var that = this;
			window.NeosMediaBrowserCallbacks = {
				close: function () {
					SecondaryInspectorController.hide(that.get('_mediaBrowserEditView'));
					that._initializeMediaBrowserEditView();
				}
			};
		},

		/****************************************
		 * IMAGE REMOVE
		 ***************************************/
		remove: function () {
			if (this.get('_mediaBrowserEditView')) {
				SecondaryInspectorController.hide(this.get('_mediaBrowserEditView'));
				this.set('_mediaBrowserEditView', null);
			}
			if (this.get('_mediaBrowserView')) {
				SecondaryInspectorController.hide(this.get('_mediaBrowserView'));
			}
			this.set('_object', null);
			this.set('_originalImageUri', null);
			this.set('_previewImageUri', null);
			this.set('_finalImageDimensions.width', null);
			this.set('_finalImageDimensions.height', null);
			this.set('value', '');
		},

		cancel: function () {
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
		filesScheduledForUpload: function (files) {
			if (files.length > 0) {
				this._displayImageLoader();
				this.upload();
			}
		},

		/**
		 * Callback after file upload is complete
		 */
		fileUploaded: function (response) {
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
			Ember.endPropertyChanges();
			this.set('_imageFullyLoaded', false);
			this.set('_imageFullyLoaded', true);
			this._updateValue();

			if (SecondaryInspectorController.get('_viewClass') === this.get('_cropView')) {
				// Set empty view for secondary inspector to re-initialize crop view
				SecondaryInspectorController.toggle(Ember.View.extend());
				SecondaryInspectorController.hide();
			}
		},

		_resetCropPropertiesToCurrentPreviewImageDimensions: function () {
			this.set('_cropProperties.width', this.get('_previewImageDimensions.width'));
			this.set('_cropProperties.height', this.get('_previewImageDimensions.height'));
			this.set('_cropProperties.x', 0);
			this.set('_cropProperties.y', 0);
		},

		_imageFullyLoadedDidChange: function () {
			// Apply locked aspect ratio if defined
			if (this.get('_imageFullyLoaded') === true && this.get('crop.aspectRatio.locked.width') > 0) {
				var lockedAspectRatioWidth = this.get('crop.aspectRatio.locked.width'),
					lockedAspectRatioHeight = this.get('crop.aspectRatio.locked.height'),
					lockedAspectRatio = lockedAspectRatioWidth / lockedAspectRatioHeight;
				// Check if the existing crop aspect ratio matches the locked aspect ratio (with a margin for rounded issues)
				if (Math.round(this.get('_cropProperties.aspectRatio') * 20) / 20 !== Math.round(lockedAspectRatio * 20) / 20) {
					if (lockedAspectRatioWidth > lockedAspectRatioHeight) {
						this.set('_cropProperties.height', parseInt(this.get('_cropProperties.height') / lockedAspectRatio * this.get('_cropProperties.aspectRatio'), 10));
					} else if (lockedAspectRatioWidth === lockedAspectRatioHeight) {
						if (this.get('_cropProperties.width') > this.get('_cropProperties.height')) {
							this.set('_cropProperties.width', parseInt(this.get('_cropProperties.width') * lockedAspectRatio / this.get('_cropProperties.aspectRatio'), 10));
						} else {
							this.set('_cropProperties.height', parseInt(this.get('_cropProperties.height') / lockedAspectRatio * this.get('_cropProperties.aspectRatio'), 10));
						}
					} else {
						this.set('_cropProperties.width', parseInt(this.get('_cropProperties.width') * lockedAspectRatio / this.get('_cropProperties.aspectRatio'), 10));
					}
				}
			}
		}.observes('_imageFullyLoaded'),

		/****************************************
		 * CROPPING
		 ***************************************/
		_cropView: function () {
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
				aspectRatioDefaultOption: null,
				aspectRatioOptions: [],
				api: null,
				selection: null,
				initialized: false,

				init: function () {
					this._super();
					var that = this,
						configuration = parent.get('crop');
					if (configuration.aspectRatio) {
						if (configuration.aspectRatio.locked && configuration.aspectRatio.locked.width > 0 && configuration.aspectRatio.locked.height > 0) {
							this.setProperties({
								'aspectRatioWidth': configuration.aspectRatio.locked.width,
								'aspectRatioHeight': configuration.aspectRatio.locked.height
							});
							this.set('aspectRatioLocked', true);
						} else {
							if (parent._shouldApplyCrop(parent.get('_cropProperties.full'), parent.get('_previewImageDimensions.width'), parent.get('_previewImageDimensions.height'))) {
								this.setProperties({
									'aspectRatioWidth': parent.get('_cropProperties.width'),
									'aspectRatioHeight': parent.get('_cropProperties.height')
								});
							}
							if (configuration.aspectRatio.defaultOption) {
								this.set('aspectRatioDefaultOption', configuration.aspectRatio.defaultOption);
							}
							var options = Ember.A(),
								option = Ember.Object.extend({
									init: function () {
										this._super();
										var reducedRatio = that.reduceRatio(this.get('width'), this.get('height'));
										this.set('reducedNominator', reducedRatio[0]);
										this.set('reducedDenominator', reducedRatio[1]);
									},
									label: function () {
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

				_selectionDidChange: function () {
					var option = this.get('aspectRatioOptions').findBy('key', this.get('selection'));
					if (!option) {
						return;
					}
					clearTimeout(this.get('customTimeout'));
					if (option.get('key') !== 'custom') {
						this.setProperties({'aspectRatioWidth': option.width, 'aspectRatioHeight': option.height});
					} else {
						var that = this;
						// Use timeout since selection is changed multiple times
						this.set('customTimeout', setTimeout(function () {
							if (that.$().find('input[type="number"]:focus').length === 0) {
								that.$().find('.neos-image-editor-crop-aspect-ratio input[type="number"]:first').focus();
							}
						}, 50));
					}
				}.observes('selection'),

				aspectRatioReduced: function () {
					var aspectRatioReducedNumerator = this.get('aspectRatioReducedNumerator'),
						aspectRatioReducedDenominator = this.get('aspectRatioReducedDenominator');
					return aspectRatioReducedNumerator ? aspectRatioReducedNumerator + ':' + aspectRatioReducedDenominator : '';
				}.property('aspectRatioReducedNumerator', 'aspectRatioReducedDenominator'),

				originalAspectRatio: function () {
					var aspectRatioWidth = this.get('aspectRatioWidth'),
						aspectRatioHeight = this.get('aspectRatioHeight'),
						originalImageWidth = parent.get('_originalImageDimensions.width'),
						originalImageHeight = parent.get('_originalImageDimensions.height');
					return aspectRatioWidth / aspectRatioHeight === originalImageWidth / originalImageHeight;
				}.property('aspectRatioWidth', 'aspectRatioHeight'),

				selectedAspectRatioOption: function () {
					var aspectRatioWidth = this.get('aspectRatioWidth'),
						aspectRatioHeight = this.get('aspectRatioHeight'),
						aspectRatio = aspectRatioWidth / aspectRatioHeight,
						matchesOption = false,
						options = this.get('aspectRatioOptions');
					options.forEach(function (option) {
						// First iteration with a strict match
						if (Math.round(option.width / option.height * 50) / 50 === Math.round(aspectRatio * 50) / 50) {
							option.set('active', true);
							matchesOption = true;
						} else {
							option.set('active', false);
						}
					});
					if (!matchesOption) {
						// Secondary iteration if no matches were found with a less strict match
						options.forEach(function (option) {
							if (Math.round(option.width / option.height * 20) / 20 === Math.round(aspectRatio * 20) / 20) {
								option.set('active', true);
								matchesOption = true;
							} else {
								option.set('active', false);
							}
						});
					}
					// Select custom if allowed + it's not the original aspect ratio + no other options matched + aspect ratio width & height set + not during initialization
					if (this.get('aspectRatioAllowCustom') && !this.get('originalAspectRatio') && !matchesOption && aspectRatioWidth > 0 && aspectRatioHeight > 0 && this.get('initialized')) {
						options.findBy('label', 'Custom').set('active', true);
					}
					var activeOption = options.findBy('active', true);
					if (activeOption) {
						this.set('selection', activeOption.get('key'));
					} else {
						this.set('selection', null);
					}
					var that = this;
					Ember.run.next(function () {
						that.$().find('select').trigger('change');
					});
				}.observes('aspectRatioWidth', 'aspectRatioHeight').on('init'),

				_aspectRatioDidChange: function () {
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
					exchangeAspectRatio: function () {
						var aspectRatioWidth = this.get('aspectRatioWidth'),
							aspectRatioHeight = this.get('aspectRatioHeight');
						this.setProperties({
							'aspectRatioWidth': aspectRatioHeight,
							'aspectRatioHeight': aspectRatioWidth
						});
					}
				},

				/**
				 * Reduce a numerator and denominator to it's smallest, integer ratio using Euclid's Algorithm
				 */
				reduceRatio: function (numerator, denominator) {
					var temp,
						divisor,
						isInteger = function (n) {
							return n % 1 === 0;
						},
						greatestCommonDivisor = function (a, b) {
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

				didInsertElement: function () {
					var $image = this.$().find('img');
					$image.attr('src', parent.get('_previewImageUri'));

					var update = function (previewImageCoordinates) {
							var imageWidthBeforeChange = parent.get('_finalImageDimensions.width');
							var imageWidthScalingFactor = previewImageCoordinates.w / parent.get('_cropProperties.width');
							Ember.beginPropertyChanges();
							if (imageWidthBeforeChange) {
								parent.set('_finalImageDimensions.width', Math.round(imageWidthBeforeChange * imageWidthScalingFactor));
							}

							parent.set('_cropProperties.width', previewImageCoordinates.w);
							parent.set('_cropProperties.height', previewImageCoordinates.h);
							parent.set('_cropProperties.x', previewImageCoordinates.x);
							parent.set('_cropProperties.y', previewImageCoordinates.y);
							Ember.endPropertyChanges();
							parent._updateValue();
						},
						settings = {
							boxWidth: 600,
							boxHeight: 600,
							// Triggered when the selection is finished or updated
							onSelect: update,
							onChange: update
						};

					var cropOptions = parent.get('_cropProperties.full');
					// If we have all crop options set, we preselect this in the cropping tool.
					if (parent.get('_cropProperties.initialized')) {

						settings.setSelect = [
							cropOptions.x,
							cropOptions.y,
							cropOptions.x + cropOptions.width,
							cropOptions.y + cropOptions.height
						];
					}

					if (this.get('aspectRatioWidth') && this.get('aspectRatioHeight')) {
						settings.aspectRatio = this.get('aspectRatioWidth') / this.get('aspectRatioHeight');
					} else if (this.get('aspectRatioDefaultOption') && !parent._shouldApplyCrop(cropOptions, parent.get('_previewImageDimensions.width'), parent.get('_previewImageDimensions.height'))) {
						var defaultOption = this.get('aspectRatioOptions').findBy('key', this.get('aspectRatioDefaultOption'));
						defaultOption.set('active', true);
						this.set('selection', defaultOption.get('key'));
						settings.aspectRatio = defaultOption.width / defaultOption.height;
					}

					var that = this;
					$image.Jcrop(settings, function () {
						that.set('api', this);
					});

					this.$().find('select').select2({
						maximumSelectionSize: 1,
						minimumResultsForSearch: 10,
						allowClear: true,
						placeholder: 'Aspect ratio',
						dropdownCssClass: 'neos-select2-large'
					});

					this.set('initialized', true);
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
		_updateCropPreviewImage: function () {
			var that = this;
			// Make sure the image has been updated before altering styles
			Ember.run.next(function () {
				var cropProperties = that.get('_cropProperties.full'),
					container = that.$().find('.neos-inspector-image-thumbnail-inner'),
					image = container.find('img');

				if (that.get('_originalImageUri') && that._shouldApplyCrop(cropProperties, that.get('_previewImageDimensions.width'), that.get('_previewImageDimensions.height'))) {
					var scalingFactorX = that.imagePreviewMaximumDimensions.width / cropProperties.width,
						scalingFactorY = that.imagePreviewMaximumDimensions.height / cropProperties.height,
						overallScalingFactor = Math.min(scalingFactorX, scalingFactorY),
						previewBoundingBoxDimensions = {
							width: Math.floor(cropProperties.width * overallScalingFactor),
							height: Math.floor(cropProperties.height * overallScalingFactor)
						};

					// Update size of preview bounding box and center preview image thumbnail
					container.css({
						width: previewBoundingBoxDimensions.width + 'px',
						height: previewBoundingBoxDimensions.height + 'px',
						position: 'absolute',
						left: ((that.imagePreviewMaximumDimensions.width - previewBoundingBoxDimensions.width) / 2 ) + 'px',
						top: ((that.imagePreviewMaximumDimensions.height - previewBoundingBoxDimensions.height) / 2) + 'px'
					}).addClass('neos-inspector-image-thumbnail-cropped');

					// Scale Preview image and update relative image position
					image.css({
						width: Math.floor(that.get('_previewImageDimensions').width * overallScalingFactor) + 'px',
						height: Math.floor(that.get('_previewImageDimensions').height * overallScalingFactor) + 'px',
						marginLeft: '-' + (cropProperties.x * overallScalingFactor) + 'px',
						marginTop: '-' + (cropProperties.y * overallScalingFactor) + 'px'
					});
				} else {
					container.attr('style', null).removeClass('neos-inspector-image-thumbnail-cropped');
					image.attr('style', null);
				}
			});
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
		_updateValue: function () {
			if (!this.get('_imageFullyLoaded')) {
				return;
			}

			var currentObject = this.get('_object'),
				newImageVariantData = {},
				that = this;

			this._applyEditorChangesToAdjustments();
			if (currentObject !== null && Object.keys(this.get('_adjustments')).length > 0) {

				newImageVariantData = {
					adjustments: this.get('_adjustments'),
					__type: 'Neos\\Media\\Domain\\Model\\ImageVariant',
					// we need to transform Image into an ImageVariant if we added adjustments.
					originalAsset: currentObject.originalAsset ? currentObject.originalAsset.__identity : currentObject.__identity
				};
			}

			if (newImageVariantData.__type === 'Neos\\Media\\Domain\\Model\\ImageVariant') {
				delete newImageVariantData.__type;
				this.set('value', {
					_propertyChangePromiseClosure: function () {
					return HttpClient.createResource(that.get('_createImageVariantEndpointUri'), {data: {asset: newImageVariantData}});
				}});
			} else {
				this.set('value', JSON.stringify(currentObject));
			}
		},

		/**
		 * On startup, we deserialize the JSON string found in the editor's "value" property and fill _originalImage,
		 * _scaleOptions and _cropOptions with their respective values.
		 */
		_readAndDeserializeValue: function () {
			var that = this,
				imageVariant = this.get('value');

			if (!imageVariant || !Utility.isValidJsonString(imageVariant)) {
				return;
			}
			try {
				imageVariant = JSON.parse(imageVariant);
			} catch (e) {
				// In case we do not have valid JSON here, let's silently return
				return;
			}
			if (imageVariant) {
				// We now load more detailed data for the image variant, and as soon as we have it fully initialize
				// the widget.
				this._displayImageLoader();

				that.set('_loadPreviewImageHandler', HttpClient.getResource(
					that.get('_imageServiceEndpointUri') + '?image=' + imageVariant.__identity,
					{dataType: 'json'}
				));
				that.get('_loadPreviewImageHandler').then(function (metadata) {
					that._hideImageLoader();
					that.beginPropertyChanges();
					that._applyLoadedMetadata(metadata);
					that._updateAdjustmentsFromObject();
					that._resetCropPropertiesToCurrentPreviewImageDimensions();
					that.endPropertyChanges();
					that._updateFinalImageDimensions();
					that.set('_imageFullyLoaded', true);
				});
			}
		},

		_applyLoadedMetadata: function (metadata) {
			this.set('_object', metadata.object);
			this.set('_originalImageDimensions', metadata.originalDimensions);
			this.set('_originalImageUri', metadata.originalImageResourceUri);
			this.set('_previewImageDimensions', metadata.previewDimensions);
			this.set('_previewImageUri', metadata.previewImageResourceUri);

			// FIXME: Make less hardcoded... Currently svg is probably the most important format to support, but other formats could also need disabling. Find a better way to define formats vs. features.
			if (metadata.mediaType === 'image/svg+xml') {
				this.set('features', {crop: false, resize: false});
			}
		},

		_applyEditorChangesToAdjustments: function () {
			// Prevent the user from setting width and height to empty
			var finalWidth = this.get('_finalImageDimensions.width'),
				finalHeight = this.get('_finalImageDimensions.height'),
				originalWidth = this.get('_originalImageDimensions.width'),
				originalHeight = this.get('_originalImageDimensions.height'),
				cropProperties = this._convertCropOptionsFromPreviewImageCoordinates(this.get('_cropProperties.full'));

			if ((finalWidth > 0 && finalHeight > 0) && (this._adjustments['Neos\\Media\\Domain\\Model\\Adjustment\\ResizeImageAdjustment'] || (finalWidth !== originalWidth || finalHeight !== originalHeight))) {
				this._applyResizeAdjustment(finalWidth, finalHeight);
			}

			if (this._adjustments['Neos\\Media\\Domain\\Model\\Adjustment\\CropImageAdjustment'] || this._shouldApplyCrop(cropProperties, originalWidth, originalHeight)) {
				this._applyCropAdjustment(cropProperties);
			}
		},

		_applyResizeAdjustment: function (finalWidth, finalHeight) {
			// in order for change detection to work the order of these properties needs to be exactly like received from the server side (which is alphabetically ordered)
			this._adjustments['Neos\\Media\\Domain\\Model\\Adjustment\\ResizeImageAdjustment'] = {
				allowUpScaling: null,
				height: finalHeight,
				maximumHeight: null,
				maximumWidth: null,
				minimumHeight: null,
				minimumWidth: null,
				position: 20,
				ratioMode: null,
				width: finalWidth
			};
		},

		_shouldApplyCrop: function (cropProperties, imageWidth, imageHeight) {
			if (imageWidth == null || imageHeight == null) {
				return false;
			}

			return !(
				cropProperties.width === imageWidth &&
				cropProperties.height === imageHeight &&
				cropProperties.x === 0 &&
				cropProperties.y === 0
			);
		},

		_applyCropAdjustment: function (cropProperties) {
			// in order for change detection to work the order of these properties needs to be exactly like received from the server side (which is alphabetically ordered)
			this._adjustments['Neos\\Media\\Domain\\Model\\Adjustment\\CropImageAdjustment'] = {
				height: cropProperties.height,
				position: 10,
				width: cropProperties.width,
				x: cropProperties.x,
				y: cropProperties.y
			};
		},

		_updateImageEditLink: function () {
			var that = this;
			if (!this.get('_object')) {
				this.set('_mediaBrowserEditView', null);
				return;
			}

			this._initializeMediaBrowserEditView();

			this.$().find('.neos-inspector-image-thumbnail').click(function () {
				var view = that.get('_mediaBrowserEditView');
				if (view === null) {
					return;
				}
				if (!SecondaryInspectorController._viewIsActive(view)) {
					that._beforeMediaBrowserEditIsShown();
				}
				SecondaryInspectorController.toggle(view);
			});
		}.observes('_imageFullyLoaded'),

		/**
		 *
		 */
		_updateAdjustmentsFromObject: function () {
			var that = this,
				adjustments = {};
			if (this.get('_object.adjustments')) {
				adjustments = this.get('_object.adjustments');
				$.each(adjustments, function (index, adjustment) {
					if (index === 'Neos\\Media\\Domain\\Model\\Adjustment\\CropImageAdjustment') {
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
					if (index === 'Neos\\Media\\Domain\\Model\\Adjustment\\ResizeImageAdjustment') {
						that.set('_finalImageDimensions.width', Ember.get(adjustment, 'width'));
						that.set('_finalImageDimensions.height', Ember.get(adjustment, 'height'));
					}
				});
			}

			this.set('_adjustments', adjustments);
		}.observes('_object'),

		/**
		 * This is only to be used after loading image metadata because we want the final dimensions to be exactly like the resize image adjustment
		 */
		_updateFinalImageDimensions: function() {
			var adjustments = this.get('_adjustments'),
				resizeAdjustment = adjustments['Neos\\Media\\Domain\\Model\\Adjustment\\ResizeImageAdjustment'];
			if (resizeAdjustment) {
				this.set('_finalImageDimensions.width', resizeAdjustment.width);
				this.set('_finalImageDimensions.height', resizeAdjustment.height);
			}
		},

		/**
		 * Helper
		 *
		 * Convert the crop options from the *preview image* coordinate system to the
		 * *master image* coordinate system which is stored persistently.
		 *
		 * The inverse function to this method is _convertCropOptionsToPreviewImageCoordinates
		 */
		_convertCropOptionsFromPreviewImageCoordinates: function (previewImageCoordinates) {
			var previewImageDimensions = this.get('_previewImageDimensions'),
				originalImageDimensions = this.get('_originalImageDimensions');

			return {
				x: Math.round(previewImageCoordinates.x * (originalImageDimensions.width / previewImageDimensions.width)),
				y: Math.round(previewImageCoordinates.y * (originalImageDimensions.height / previewImageDimensions.height)),
				width: Math.round(previewImageCoordinates.width * (originalImageDimensions.width / previewImageDimensions.width)),
				height: Math.round(previewImageCoordinates.height * (originalImageDimensions.height / previewImageDimensions.height))
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
		_convertCropOptionsToPreviewImageCoordinates: function (coordinates) {
			var previewImageDimensions = this.get('_previewImageDimensions'),
				originalImageDimensions = this.get('_originalImageDimensions');

			return {
				x: Math.round(coordinates.x / (originalImageDimensions.width / previewImageDimensions.width)),
				y: Math.round(coordinates.y / (originalImageDimensions.height / previewImageDimensions.height)),
				width: Math.round(coordinates.width / (originalImageDimensions.width / previewImageDimensions.width)),
				height: Math.round(coordinates.height / (originalImageDimensions.height / previewImageDimensions.height))
			};
		},

		_initializeMediaView: function () {
			this.set('_mediaBrowserView', Ember.View.extend({
				template: Ember.Handlebars.compile('<iframe style="width:100%; height: 100%" src="' + $('link[rel="neos-image-browser"]').attr('href') + '"></iframe>'),
				didInsertElement: function() {
					this.$().find('iframe').on('load', function(event) {
						if (window.NeosMediaBrowserCallbacks && window.NeosMediaBrowserCallbacks.onLoad) {
							window.NeosMediaBrowserCallbacks.onLoad(event, this);
						}
					});
				}
			}));
		},

		_initializeUploader: function () {
			this._super();

			this._uploader.bind('BeforeUpload', function (uploader, file) {
				uploader.settings.multipart_params.metadata = 'Image';
			});

			var that = this;
			this._uploader.bind('Error', function (uploader, error) {
				that.cancel();
			});
		},

		/**
		 * Computed property to decide if cropping is available in the editor
		 */
		shouldRenderCrop: function () {
			return (this.get('features.crop') && this.get('_originalImageUri'));
		}.property('features.crop', '_originalImageUri'),

		/**
		 * Computed property to decide if resizing is available in the editor
		 */
		shouldRenderResize: function () {
			return (this.get('features.resize') && this.get('_originalImageUri'));
		}.property('features.resize', '_originalImageUri'),

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
		},

		initializeCropButtonTooltip: function() {
			// Re-initialize crop button tooltip after re-render
			Ember.run.next(this, function() {
				this.$('[data-neos-tooltip]:not([data-original-title])').tooltip({container: '#neos-application'});
			});
		}.observes('shouldRenderCrop')
	});
});
