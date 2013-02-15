/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'jquery',
	'text!neos/templates/content/ui/fileUpload.html',
	'text!neos/templates/content/ui/imageUpload.html',
	'neos/content/ui/elements',
	'canvas.indicator'
],
function($, fileUploadTemplate, imageUploadTemplate) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/editors');

	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}
	T3.Content.UI = T3.Content.UI || {};
	T3.Content.UI.Editor = T3.Content.UI.Editor || {};

	Ember.TextSupport.reopen({
		attributeBindings: ['name', 'required', 'pattern', 'step', 'min', 'max']
	});

	T3.Content.UI.Editor.TextField = Ember.TextField.extend({
		classNames: ['input-small']
	});

	T3.Content.UI.Editor.Checkbox = Ember.Checkbox.extend({
		/**
		 * The value attribute of Ember.Checkbox is renamed to 'checked' in Emberjs 0.9.7
		 * This method is a wrapper to make sure this Editor object still has a value property.
		 */
		value: function(key, value) {
			if (arguments.length === 2) {
				this.set('checked', value);
				return value;
			} else {
				return this.get('checked');
			}
		}.property('checked').cacheable()
	});

	T3.Content.UI.Editor.DateField = Ember.TextField.extend({
		classNames: ['input-small'],
		didInsertElement: function() {
			this.$().attr('placeholder', 'No date set');
			this.$().datepicker({
				dateFormat: $.datepicker.W3C,
				beforeShow: function(field, datePicker) {
					$(datePicker.dpDiv).addClass('t3-ui');
				}
			});
		}
	});

	T3.Content.UI.Editor.SelectboxOption = Ember.View.extend({
		tagName: 'option',
		attributeBindings: ['value', 'selected'],
		valueBinding: 'content.value',
		selectedBinding: 'content.selected',

		template: Ember.Handlebars.compile('{{content.label}}')
	});

	T3.Content.UI.Editor.Selectbox = Ember.CollectionView.extend({
		classNames: ['typo3-form-selectbox'],

		tagName: 'select',
		contentBinding: 'options',
		itemViewClass: T3.Content.UI.Editor.SelectboxOption,

		value: '',
		allowEmpty: false,
		placeholder: '',

		attributeBindings: ['size', 'disabled'],

		values: [],

		options: function() {
			var options = [], currentValue = this.get('value');

			if (this.get('allowEmpty')) {
				options.push(Ember.Object.create({value: '', label: this.get('placeholder')}));
			}
			$.each(this.get('values'), function(value) {
				options.push(Ember.Object.create($.extend({
					selected: value === currentValue,
					value: value
				}, this)));
			});
			return options;
		}.property('values', 'value', 'placeholder', 'allowEmpty').cacheable(),

		onItemsChange: function() {
			// Special event for chosen
			this.$().trigger("liszt:updated");
		}.observes('values'),

		didInsertElement: function() {
			var that = this;

			if (this.get('placeholder')) {
				this.$().attr('data-placeholder', this.get('placeholder'));
			}

			require([
					'chosen'
				], function() {
					// TODO Check value binding
					that.$().addClass('chzn-select').chosen().change(function() {
						that.set('value', that.$().val());
					});
				}
			);
		}
	});

	T3.Content.UI.Editor.HtmlEditor = T3.Content.UI.PopoverButton.extend({

		_editorInitialized: false,

		_editor: null,

		// TODO: fix the width / height so it relates to the rest of the UI
		$popoverContent: $('<div />').attr('class', 't3-ui t3-htmleditor-window'),

		label: 'HTML Editor',

		popoverTitle: 'HTML Editor',

		popoverPosition: 'left',

		classNames: ['t3-primary-editor-action'],

		onPopoverOpen: function() {
			var that = this,
				id = this.get(Ember.GUID_KEY);

				// Initialize CodeMirror editor with a nice html5 canvas demo.
			if (!this._editorInitialized) {

				var $editorContent = $('<textarea />', {
					id: 'typo3-htmleditor-' + id
				}).html(that.get('value'));

				this.$popoverContent.append($editorContent);

				require([
					'codemirror',
					'codemirror.xml',
					'codemirror.css',
					'codemirror.javascript',
					'codemirror.htmlmixed'
				], function() {
					var editorFullyPopulated = false;

					that._editor = CodeMirror.fromTextArea($editorContent.get(0), {
						mode: 'text/html',
						tabMode: 'indent',
						lineNumbers: true,
						onChange: function() {
							if (that._editor && editorFullyPopulated) {
								that.set('value', that._editor.getValue());
							}
						}
					});

						// We trigger an automatic indentation, which removes all the
						// automatic whitespaces etc...
					var lineCount = that._editor.lineCount();
					for(var i=0; i<lineCount; i++) {
						that._editor.indentLine(i);
					}

					editorFullyPopulated = true;
				});

				this._editorInitialized = true;
			}
		},

		willDestroyElement: function() {
			if (this._editorInitialized) {
				this.$().trigger('hidePopover');
				this._editor.toTextArea();
				$('#typo3-htmleditor-' + this.get(Ember.GUID_KEY)).remove();
				this._editorInitialized = false;
			}
			// TODO: not only hide the popover, but completely remove it from DOM!
		}

	});

	T3.Content.UI.Editor.FileUpload = Ember.View.extend({

		value: '',

		/**
		 * Label of the file chooser button
		 */
		fileChooserLabel: 'Choose file',
		uploaderLabel: 'Upload',
		cropLabel: 'Crop',

		// File filters
		allowedFileTypes: null,

		_uploader: null,
		_uploadInProgress: false,
		_containerId: null,
		_browseButtonId: null,
		_uploadButtonShown: false,
		_uploadButtonNotShown: function() {
			return !this.get('_uploadButtonShown');
		}.property('_uploadButtonShown').cacheable(),

		template: Ember.Handlebars.compile(fileUploadTemplate),

		init: function() {
			var id = this.get(Ember.GUID_KEY);
			this._containerId = 'typo3-fileupload' + id;
			this._browseButtonId = 'typo3-fileupload-browsebutton' + id;
			return this._super();
		},

		didInsertElement: function() {
			this._initializeUploader();
		},

		_initializeUploader: function() {
			var that = this;
			this._uploader = new plupload.Uploader({
				runtimes : 'html5',
				browse_button : this._browseButtonId,
				container : this._containerId,
				max_file_size : '10mb',
				url : '/neos/content/uploadImage',
				multipart_params: {}
			});
			if (this.allowedFileTypes) {
				this._uploader.settings.filters = [{
					title: 'Allowed files',
					extensions: this.allowedFileTypes
				}];
			}

			this._uploader.bind('FilesAdded', function(uploader, files) {
				if (files.length > 0) {
					that.set('_uploadButtonShown', true);
				} else {
					that.set('_uploadButtonShown', false);
				}
			});

			this._uploader.bind('Error', function(uploader, error) {
				that.set('_uploadInProgress', false);
				T3.Common.Notification.error(error.message);
				// FilesAdded gets the unfiltered list, so we have to disable the upload on errors
				if (error.code === plupload.FILE_EXTENSION_ERROR) {
					that.set('_uploadButtonShown', false);
				}
			});

			this._uploader.bind('BeforeUpload', function(uploader, file) {
				uploader.settings.multipart_params['image[type]'] = 'plupload';
				uploader.settings.multipart_params['image[fileName]'] = file.name;
			});

			this._uploader.bind('FileUploaded', function(uploader, file, response) {
				T3.Common.Notification.ok('Uploaded file "' + file.name + '".');
				that.fileUploaded(response.response);
			});

			this._uploader.init();
			this._uploaderInitialized();
		},
		_uploaderInitialized: function() {
			var that = this;
			this.$().find('input[type=file][id^="' + this._uploader.id + '"]').change(function(event) {
				that.filesScheduledForUpload(event.target.files);
			});
		},
		// The "files" is taken from the DOM event when a file changes
		filesScheduledForUpload: function(files) {
			// Template method
		},
		fileUploaded: function(response) {
			this.set('_uploadInProgress', false);
			this.set('_uploadButtonShown', false);
		},
		upload: function() {
			this.set('_uploadInProgress', true);
			this._uploader.start();
		}
	});

	/**
	 * The Image has to extend from fileUpload; as plupload just breaks with very weird
	 * error messages otherwise.
	 */
	T3.Content.UI.Editor.Image = T3.Content.UI.Editor.FileUpload.extend({

		/****************************************
		 * GENERAL SETUP
		 ***************************************/
		fileChooserLabel: 'Choose Image',

		uploaderLabel: 'Upload!',

		/**
		 * Size of the image preview. Public configuration.
		 *
		 * If this setting is changed, also the CSS properties
		 * .t3-inspector-image-uploadthumbnail-portrait and .t3-inspector-image-uploadthumbnail-landscape
		 * need to be adjusted.
		 */
		imagePreviewMaximumDimensions: {w: 178, h: 178},

		/**
		 * Comma-separated list of allowed file types.
		 * Public configuration.
		 */
		allowedFileTypes: 'jpg,jpeg,png,gif',

		template: Ember.Handlebars.compile(imageUploadTemplate),

		/**
		 * The Upload Preview is the image being shown *before* the user presses
		 * "upload".
		 */
		_uploadPreviewShown: true,
		_uploadPreviewNotShown: function() {
			return !this.get('_uploadPreviewShown');
		}.property('_uploadPreviewShown').cacheable(),

		_uploadPreviewImageSource: '',
		_defaultUploadPreviewImageSource: '/_Resources/Static/Packages/TYPO3.Neos/Images/dummy-image.png', // TODO: we need a way to fetch the static resources base URI

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
		// size of the original (base) image ("w" + "h")
		_originalImageSize: null,

		/**
		 * The "preview" image is shown in the sidebar, and is also used
		 * for cropping.
		 */
		_previewImageUri: null,

		// This is the size of the image being used for cropping
		// Object "w" + "h"
		_previewImageSize: null,

		/**
		 * Crop properties, as being used by jCrop editor.
		 * ALL COORDINATES are relative to _previewImageSize.
		 */
		_cropProperties: null,

		// After cropping, we still scale the cropped image
		_finalImageScale: null,

		// Contains the handler for the AJAX request loading the preview image
		_loadPreviewImageHandler: null,

		init: function() {
			var that = this;
			this._super();

			this.set('_uploadPreviewImageSource', this.get('_defaultUploadPreviewImageSource'));

			this.set('_finalImageScale', Ember.Object.create({
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
						this.set('w', Math.ceil(that.getPath('_cropProperties.aspectRatio') * value));
						return value;
					}
				}.property('h').cacheable()
			}));

			this.set('_cropProperties',  Ember.Object.create({
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
					}
				}.property('w', 'h', 'x', 'y').cacheable(),

				initialized: function() {
					return this.get('w') !== null && this.get('h') !== null && this.get('x') !== null && this.get('y') !== null;
				}.property('w', 'h', 'x', 'y').cacheable(),

				aspectRatio: function() {
					if (isNaN(this.get('w'))
						|| isNaN(this.get('h'))
						|| this.get('w') === 0
						|| this.get('h') === 0) {
						return 1;
					}

					return parseFloat(this.get('w')) / parseFloat(this.get('h'));
				}.property('w', 'h').cacheable()
			}));
		},

		_imageWidthToggle: function(propertyName, value) {
			if (typeof value === 'boolean') {
				if (value === false) {
					this.setPath('_finalImageScale.w', null);
					this.setPath('_finalImageScale.h', null);
				} else {
					this.setPath('_finalImageScale.w', this.getPath('_originalImageSize.w'));
					this.setPath('_finalImageScale.h', this.getPath('_originalImageSize.h'));
				}
			}
			if (this.getPath('_finalImageScale.w') > 0) {
				return true;
			}
			this.setPath('_finalImageScale.w', null);
			this.setPath('_finalImageScale.h', null);
			return false;
		}.property('_finalImageScale.w').cacheable(),

		_imageHeightToggle: function(propertyName, value) {
			if (typeof value === 'boolean') {
				if (value === false) {
					this.setPath('_finalImageScale.w', null);
					this.setPath('_finalImageScale.h', null);
				} else {
					this.setPath('_finalImageScale.w', this.getPath('_originalImageSize.w'));
					this.setPath('_finalImageScale.h', this.getPath('_originalImageSize.h'));
				}
			}
			if (this.getPath('_finalImageScale.w') > 0) {
				return true;
			}
			this.setPath('_finalImageScale.w', null);
			this.setPath('_finalImageScale.h', null);
			return false;
		}.property('_finalImageScale.h').cacheable(),

		_aspectRatioChanged: function() {
			this.setPath('_finalImageScale.h', parseInt(this.getPath('_finalImageScale.w') / this.getPath('_cropProperties.aspectRatio')));
		}.observes('_finalImageScale.w', '_cropProperties.aspectRatio'),

		/****************************************
		 * INITIALIZATION
		 ***************************************/
		/**
		 * Lifecycle callback; sets some CSS for the image preview area to sensible defaults,
		 * and reads the image if possible
		 */
		didInsertElement: function() {
			this._super();

			this.$().find('.t3-inspector-image-thumbnail-inner').css({
				width: this.imagePreviewMaximumDimensions.w + 'px',
				height: this.imagePreviewMaximumDimensions.h + 'px'
			});
			this.$().find('.t3-inspector-image-thumbnail-container').css({
				width: this.imagePreviewMaximumDimensions.w + 'px',
				height: this.imagePreviewMaximumDimensions.h + 'px'
			});

			this._readAndDeserializeValue();
		},

		willDestroyElement: function() {
				// Hide popover when the focus changes
			this.$().find('.t3-inspector-image-crop-button').trigger('hidePopover');
			if (this.get('_loadPreviewImageHandler')) {
				this.get('_loadPreviewImageHandler').abort();
			}
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

				if (window['FileReader']) {
					var reader = new FileReader();
					reader.onload = function(event) {
						var binaryData = event.target.result;
						that.set('_uploadPreviewImageSource', binaryData);

						var imageObjForFindingSize = new window.Image();
						imageObjForFindingSize.onload = function() {
							var scaleFactor,
								offset,
								image = that.$().find('.t3-inspector-image-uploadthumbnail img');
							if (imageObjForFindingSize.width > imageObjForFindingSize.height) {
								image.addClass('t3-inspector-image-uploadthumbnail-landscape').removeClass('t3-inspector-image-uploadthumbnail-portrait');

									// For landscape images, we set the margin-top correctly to align the image in the center
								scaleFactor = that.getPath('imagePreviewMaximumDimensions.w') / imageObjForFindingSize.width;
								offset = ((that.getPath('imagePreviewMaximumDimensions.h') - imageObjForFindingSize.height * scaleFactor) / 2);
								image.css({'margin-top': parseInt(offset) + 'px', 'margin-left': 0});
							} else {
								image.removeClass('t3-inspector-image-uploadthumbnail-landscape').addClass('t3-inspector-image-uploadthumbnail-portrait');

									// For portrait images, we set the margin-left correctly to align the image in the center
								scaleFactor = that.getPath('imagePreviewMaximumDimensions.h') / imageObjForFindingSize.height;
								offset = ((that.getPath('imagePreviewMaximumDimensions.w') - imageObjForFindingSize.width * scaleFactor) / 2);
								image.css({'margin-left': parseInt(offset) + 'px', 'margin-top': 0});
							}
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
			if (!T3.Common.Util.isValidJsonString(response)) {
				T3.Common.Notification.error('Tried to fetch image metadata: Unexpected result format.');
				return;
			}
			this._super();
			var responseJson = JSON.parse(response);

			this.set('_originalImageUuid', responseJson.imageUuid);
			this._setPreviewImage(responseJson);

				// We only need to set the width here; as the height is automatically
				// calculated from the aspect ratio in the cropper
			this.setPath('_finalImageScale.w', responseJson.originalSize.w);

			this._resetCropPropertiesToCurrentPreviewImageSize();
			this.set('_imageFullyLoaded', true);
			this._updateValue();
		},

		_setPreviewImage: function(responseJson) {
			Ember.beginPropertyChanges();

			this.set('_originalImageSize', responseJson.originalSize);
			this.set('_previewImageSize', responseJson.previewSize);
			this.set('_previewImageUri', responseJson.previewImageResourceUri);
			this.set('_uploadPreviewShown', false);

			Ember.endPropertyChanges();
		},

		_resetCropPropertiesToCurrentPreviewImageSize: function() {
			this.setPath('_cropProperties.x', 0);
			this.setPath('_cropProperties.y', 0);
			this.setPath('_cropProperties.w', this.getPath('_previewImageSize.w'));
			this.setPath('_cropProperties.h', this.getPath('_previewImageSize.h'));
		},

		/****************************************
		 * CROPPING
		 ***************************************/
		/**
		 * When the preview image is loaded, we initialize the popover.
		 */
		_initializePopover: function() {
			var that = this,
				$popoverContent = $('<div class="t3-inspector-image-crop" />'),
				$imageInThumbnail = $('<img />'),
				previewImageSize = that.get('_previewImageSize');

			$popoverContent.append($imageInThumbnail);
			$popoverContent.css({
				width: previewImageSize.w + 10 + 'px',
				height: previewImageSize.h + 10 + 'px'
			});

			this.$().find('.t3-inspector-image-thumbnail').click(function() {
				that.$().find('.t3-inspector-image-crop-button').trigger('hidePopover');
			});
			this.$().find('.t3-inspector-image-crop-button').popover({
				content: $popoverContent,
				header: '<span>Crop Image</span>',
				preventTop: true,
				preventBottom: true,
				preventRight: true,
				offsetY: -111,
				offsetX: -140,
				openEvent: function() {
					$imageInThumbnail.attr('src', that.get('_previewImageUri'));
					this.popover$.addClass('t3-ui');

					var settings = {
							// Triggered when the selection is finished
						onSelect: function(previewImageCoordinates) {
							Ember.beginPropertyChanges();
							that.setPath('_cropProperties.x', previewImageCoordinates.x);
							that.setPath('_cropProperties.y', previewImageCoordinates.y);
							that.setPath('_cropProperties.w', previewImageCoordinates.w);
							that.setPath('_cropProperties.h', previewImageCoordinates.h);
							Ember.endPropertyChanges();
							that._updateValue();
						}
					};

						// If we have all crop options set, we preselect this in the cropping tool.
					if (that.getPath('_cropProperties.initialized')) {
						var cropOptions = that.getPath('_cropProperties.full');

						settings.setSelect = [
							cropOptions.x,
							cropOptions.y,
							cropOptions.x + cropOptions.w,
							cropOptions.y + cropOptions.h
						];
					}
					$imageInThumbnail.Jcrop(settings);
				}
			});
		}.observes('_previewImageUri'),

		/**
		 *  Update the preview image when the crop options change or the preview image
		 * is initially loaded. This includes:
		 *
		 * - set the preview bounding box size
		 * - set the preview bounding box offset such that the image is centered
		 * - scale the preview image and sete the offsets correctly.
		 */
		_updateCropPreviewImage: function() {
			if (!this.get('_previewImageUri')) return;

			var cropProperties = this.getPath('_cropProperties.full');

			var scalingFactorX = this.imagePreviewMaximumDimensions.w / cropProperties.w;
			var scalingFactorY = this.imagePreviewMaximumDimensions.h / cropProperties.h;
			var overallScalingFactor = Math.min(scalingFactorX, scalingFactorY);

			var previewBoundingBoxSize = {
				w: Math.floor(cropProperties.w * overallScalingFactor),
				h: Math.floor(cropProperties.h * overallScalingFactor)
			};

				// Update size of preview bounding box
				// and Center preview image thumbnail
			this.$().find('.t3-inspector-image-thumbnail-inner').css({
				width: previewBoundingBoxSize.w + 'px',
				height: previewBoundingBoxSize.h + 'px',
				position: 'absolute',
				left: ((this.imagePreviewMaximumDimensions.w - previewBoundingBoxSize.w) / 2 ) + 'px',
				top: ((this.imagePreviewMaximumDimensions.h - previewBoundingBoxSize.h) / 2) + 'px'
			});

				// Scale Preview image and update relative image position
			this.$().find('.t3-inspector-image-thumbnail-inner img').css({
				width: Math.floor(this.get('_previewImageSize').w * overallScalingFactor) + 'px',
				height:  Math.floor(this.get('_previewImageSize').h * overallScalingFactor) + 'px',
				marginLeft: '-' + (cropProperties.x * overallScalingFactor) + 'px',
				marginTop: '-' + (cropProperties.y * overallScalingFactor) + 'px'
			});
		}.observes('_cropProperties.x', '_cropProperties.y', '_cropProperties.w', '_cropProperties.h', '_previewImageUri'),


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
			if (!this.getPath('_cropProperties.initialized')) return;
			if (!this.get('_imageFullyLoaded')) return;
			// Prevent the user from setting width and height to empty

			var originalImageCropDimensions = this._convertCropOptionsFromPreviewImageCoordinates(this.getPath('_cropProperties.full'));

			this.set('value', JSON.stringify({
				originalImage: this.get('_originalImageUuid'),
				processingInstructions: [
					{
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
					},
					{
						command: 'resize',
						options: {
							size: {
								width: this.getPath('_finalImageScale.w'),
								height: this.getPath('_finalImageScale.h')
							}
						}
					}
				]
			}));
		},

		/**
		 * On startup, we deserialize the JSON string and fill _originalImageUuid, _scaleOptions and _cropOptions
		 */
		_readAndDeserializeValue: function() {
			var that = this,
				imageVariant = this.get('value');

			if (!imageVariant || !T3.Common.Util.isValidJsonString(imageVariant)) {
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
				that.set('_loadPreviewImageHandler', $.get('/neos/content/imageWithMetadata/' + imageVariant.originalImage, function(result) {
					that._hideImageLoader();
					var metadata = JSON.parse(result);
					that.beginPropertyChanges();
					that._setPreviewImage(metadata);
					that.set('_originalImageUuid', imageVariant.originalImage);

					$.each(imageVariant.processingInstructions, function(index, instruction) {
						if (instruction.command === 'crop') {
							var finalSizeCropProperties = {
								x: Ember.getPath(instruction, 'options.start.x'),
								y: Ember.getPath(instruction, 'options.start.y'),
								w: Ember.getPath(instruction, 'options.size.width'),
								h: Ember.getPath(instruction, 'options.size.height')
							};

							var previewImageCropProperties = that._convertCropOptionsToPreviewImageCoordinates(finalSizeCropProperties);

							that.setPath('_cropProperties.x', previewImageCropProperties.x);
							that.setPath('_cropProperties.y', previewImageCropProperties.y);
							that.setPath('_cropProperties.w', previewImageCropProperties.w);
							that.setPath('_cropProperties.h', previewImageCropProperties.h);
						} else if (instruction.command === 'resize') {
							that.setPath('_finalImageScale.w', Ember.getPath(instruction, 'options.size.width'));
								// Height does not need to be set, as it is automatically calculated from crop properties + width
						}
					});

					that.endPropertyChanges();
					that.set('_imageFullyLoaded', true);
				}));
			}
		},
		_displayImageLoader: function() {
			var $canvas = $('<canvas class="t3-inspector-image-loadingindicator" />');
			this.$().find('.t3-inspector-image-thumbnail-container').append($canvas);
			new CanvasIndicator($canvas.get(0), {
				bars: 12,
				innerRadius: 8,
				size: [3, 15],
				rgb: [255, 255, 255],
				fps: 15
			});
		},
		_hideImageLoader: function() {
			this.$().find('.t3-inspector-image-loadingindicator').remove();
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
			var previewImageSize = this.get('_previewImageSize');
			var originalImageSize = this.get('_originalImageSize');

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
			var previewImageSize = this.get('_previewImageSize');
			var originalImageSize = this.get('_originalImageSize');

			return {
				x: parseInt(coordinates.x / (originalImageSize.w / previewImageSize.w)),
				y: parseInt(coordinates.y / (originalImageSize.h / previewImageSize.h)),
				w: parseInt(coordinates.w / (originalImageSize.w / previewImageSize.w)),
				h: parseInt(coordinates.h / (originalImageSize.h / previewImageSize.h))
			};
		}
	});
});
