/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'jquery',
	'text!phoenix/templates/content/ui/fileUpload.html',
	'text!phoenix/templates/content/ui/imageUpload.html',
	'phoenix/content/ui/elements'
],
function(jQuery, fileUploadTemplate, imageUploadTemplate) {
	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}
	T3.Content.UI = T3.Content.UI || {};
	T3.Content.UI.Editor = T3.Content.UI.Editor || {};
	var $ = jQuery;

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
					$(datePicker.dpDiv).addClass('aloha-block-do-not-deactivate');
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
		$popoverContent: $('<div />').attr('class', 'aloha-block-do-not-deactivate t3-htmleditor-window'),

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

		uploaderLabel: 'Upload!',

		// File filters
		allowedFileTypes: null,

		_uploader: null,
		_containerId: null,
		_browseButtonId: null,
		_uploadButtonShown: false,

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
				url : '/typo3/content/uploadImage',
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
			this.set('_uploadButtonShown', false);
		},
		upload: function() {
			this._uploader.start();
		}
	});

	/**
	 * The Image has to extend from fileUpload; as plupload just breaks with very weird
	 * error messages otherwise.
	 */
	T3.Content.UI.Editor.Image = T3.Content.UI.Editor.FileUpload.extend({

		fileChooserLabel: 'Choose Image',

		uploaderLabel: 'Upload!',

		/**
		 * Size of the image preview. Public configuration.
		 */
		imagePreviewMaximumDimensions: {width: 160, height: 160},

		/**
		 * Comma-separated list of allowed file types.
		 * Public configuration.
		 */
		allowedFileTypes: 'jpg,png',

		template: Ember.Handlebars.compile(imageUploadTemplate),

		// Upload Preview
		_uploadPreviewShown: true,
		_uploadPreviewNotShown: function() {
			return !this.get('_uploadPreviewShown');
		}.property('_uploadPreviewShown'),

		_uploadPreviewImageSource: '/_Resources/Static/Packages/TYPO3.TYPO3/Images/dummy-image.jpg', // TODO: we need a way to fetch the static resources base URI
		_previewImageSize:null,
		_previewImageUri:null,
		_imageUuid:null,

		// Transformation options

		/**
		 * Sizing
		 *
		 * For the image sizing we use a computed property for the _aspectRatio, this _aspectRatio is based on the
		 * width and height set in the _cropOptions (managed by the jCrop editor).
		 * The _imageWidth is just a property, and is watched by the _imageHeight computed property.
		 * We base only the _imageHeight on _imageWidth and not the other way around because we
		 * would end up in recursion if we let those properties watch each other.
		 *
		 * _imageWidth and _imageWidthHeight are placeholders to prevent recursion in databinding.
		 */
		_cropProperties: Ember.Object.create({
			width: null,
			height: null,
			x: null,
			y: null,
			full: function() {
				return {
					width: this.get('width'),
					height: this.get('height'),
					x: this.get('x'),
					y: this.get('y')
				}
			}.property('width', 'height', 'x', 'y').cacheable(),
			initialized: function() {
				return this.get('width') !== null && this.get('height') !== null && this.get('x') !== null && this.get('y') !== null;
			}.property('width', 'height', 'x', 'y').cacheable()
		}),

		_imageProperties: Ember.Object.create({
			width: null,
			height: null
		}),

		/**
		 * Calculate the aspect ratio of the crop proportions, uses ratio 1 if it's not possible
		 * to calculate a ratio based on the _cropProperties object
		 */
		_aspectRatio: function() {
			if (isNaN(this._cropProperties.get('width'))
					|| isNaN(this._cropProperties.get('height'))
					|| this._cropProperties.get('width') === 0
					|| this._cropProperties.get('height') === 0) {
				return 1;
			}
			return parseFloat(this._cropProperties.get('width') / this._cropProperties.get('height'));
		}.property('_cropProperties.width', '_cropProperties.height').cacheable(),

		_aspectRatioChanged: function() {
			this._imageProperties.set('height', parseInt(this._imageProperties.get('width') / this.get('_aspectRatio')));
		}.observes('_aspectRatio'),

		_imageWidth: function(key, value) {
			if (arguments.length === 1) {
				return parseInt(this._imageProperties.get('width'));
			} else {
				value = parseInt(value);

				if (value > this._imageProperties.get('width') + 1 || value < this._imageProperties.get('height') - 1) {
					this._imageProperties.set('width', value);
					this._imageProperties.set('height', parseInt(this._imageProperties.get('width') / this.get('_aspectRatio')));
					this._updateValue();
				}

				return value;
			}
		}.property('_cropProperties.width'), // This property is NOT cacheable!!!

		_imageHeight: function(key, value) {
			if (arguments.length === 1) {
				return this._imageProperties.get('height');
			} else {
				value = parseInt(value);

				if (value > this._imageProperties.get('height') + 1 || value < this._imageProperties.get('height') - 1) {
					this._imageProperties.set('height', parseInt(value));
					this._imageProperties.set('width', parseInt(this._imageProperties.get('height') * this.get('_aspectRatio')));
					this._updateValue();
				}

				return value;
			}
		}.property('_cropProperties.height'), // This property is NOT cacheable!!!

		// Cropping
		_originalImageSize:null,

		_cropOptions: function(key, value) {
			if (arguments.length === 1) {
				return this._cropProperties.get('full');
			} else {
				this._cropProperties.set('width', parseInt(value.w) || null);
				this._cropProperties.set('height', parseInt(value.h) || null);
				this._cropProperties.set('x', parseInt(value.x) || null);
				this._cropProperties.set('y', parseInt(value.y) || null);
				return value;
			}
		}.property('_cropProperties.width', '_cropProperties.height', '_cropProperties.x', '_cropProperties.y').cacheable(),

		// Image Badge
		_imageBadgeVisible: false,

		_imageBadgeClass: function() {
			if (this.get('_imageBadgeVisible')) {
				return 'typo3-imagebadge typo3-imagebadge-visible';
			}
			return 'typo3-imagebadge';
		}.property('_imageBadgeVisible').cacheable(),

		/**
		 * Lifecycle callback; sets some CSS for the image preview area to sensible defaults.
		 */
		didInsertElement: function() {
			var that = this;
			this._super();

			if (!this._cropProperties.get('initialized')) {
				this.set('_imageWidth', this.imagePreviewMaximumDimensions.width);
				this.set('_imageHeight', this.imagePreviewMaximumDimensions.height);

				this._resetCropPropertiesToCurrentImageSize();
			}

			this.$().find('.typo3-imagethumbnail-inner').css({
				width: this.imagePreviewMaximumDimensions.width + 'px',
				height: this.imagePreviewMaximumDimensions.height + 'px'
			});
			this.$().find('.typo3-imagethumbnailcontainer').css({
				width: this.imagePreviewMaximumDimensions.width + 'px',
				height: this.imagePreviewMaximumDimensions.height + 'px'
			});

			this._readAndDeserializeValue();
			if (this.get('_imageUuid')) {

				// Image already preselected
				this.set('_uploadPreviewImageSource', ' ');
				$.get('/typo3/content/imageWithMetadata/' + this.get('_imageUuid'), function(result) {

					if (T3.Common.Util.isValidJsonString(result)) {
						var metadata = JSON.parse(result);
					}
					that._setPreviewImage(metadata);
					that._updateCropPreviewImage();
				});
			}
		},

		_resetCropPropertiesToCurrentImageSize: function() {
			this._cropProperties.set('x', 0);
			this._cropProperties.set('y', 0);
			this._cropProperties.set('width', this.get('_imageWidth'));
			this._cropProperties.set('height', this.get('_imageHeight'));
		},

		/**
		 * Display Image preview
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
							if (imageObjForFindingSize.width > imageObjForFindingSize.height) {
								that.$().find('.typo3-uploadthumbnail img').addClass('typo3-fileupload-thumbnail-landscape');
								that.$().find('.typo3-uploadthumbnail img').removeClass('typo3-fileupload-thumbnail-portrait');
							} else {
								that.$().find('.typo3-uploadthumbnail img').removeClass('typo3-fileupload-thumbnail-landscape');
								that.$().find('.typo3-uploadthumbnail img').addClass('typo3-fileupload-thumbnail-portrait');
							}
							that.set('_uploadPreviewShown', true);
							that.set('_imageBadgeVisible', false);
						};
						imageObjForFindingSize.src = binaryData;
					};

					reader.readAsDataURL(image);
				}
			}
		},

		fileUploaded: function(response) {
			if (!T3.Common.Util.isValidJsonString(response)) {
				T3.Common.Notification.error('Tried to fetch image metadata: Unexpected result format.');
				return;
			}
			this._super();
			var responseJson = JSON.parse(response);

			this.set('_imageUuid', responseJson.imageUuid);
			this.set('_imageWidth', responseJson.originalSize.width);
			this.set('_imageHeight', responseJson.originalSize.height);

			this._resetCropPropertiesToCurrentImageSize();

			this._setPreviewImage(responseJson);
			this._updateValue();
		},

		_setPreviewImage: function(responseJson) {
			Ember.beginPropertyChanges();

			this.set('_originalImageSize', responseJson.originalSize);
			this.set('_previewImageSize', responseJson.previewSize);
			this.set('_previewImageUri', responseJson.previewImageResourceUri);
			this.set('_uploadPreviewShown', false);
			this.set('_imageBadgeVisible', true);

			Ember.endPropertyChanges();
		},

		/**
		 * When the preview image is loaded, we initialize the popover.
		 */
		_initializePopover: function() {
			var that = this;
			var $popoverContent = $('<div />');
			var $imageInThumbnail = $('<img />');
			$popoverContent.append($imageInThumbnail);

			var previewImageSize = that.get('_previewImageSize');

			$popoverContent.css({
				width: previewImageSize.width + 10 + 'px',
				height: previewImageSize.height + 10 + 'px',
				'padding-left': '5px',
				'padding-top': '5px',
				'background': 'black'
			});

			this.$().find('.typo3-imagethumbnail').popover({
				content: $popoverContent,
				header: '<span>Crop Image</span>',
				preventTop: true,
				preventBottom: true,
				preventRight: true,
				openEvent: function() {
					$imageInThumbnail.attr('src', that.get('_previewImageUri'));
					this.popover$.addClass('aloha-block-do-not-deactivate');
					var cropOptions = that.get('_cropOptions');

					var settings = {
							// Triggered when the selection is finished
						onSelect: function(previewImageCoordinates) {
							var newCropProperties = that._convertCropOptionsFromPreviewImageCoordinates(previewImageCoordinates);
							that._cropProperties.set('x', newCropProperties.x);
							that._cropProperties.set('y', newCropProperties.y);
							that._cropProperties.set('width', newCropProperties.width);
							that._cropProperties.set('height', newCropProperties.height);
							that._updateValue();
						}
					};

						// If we have all crop options set, we preselect this in the cropping tool.
					if (that._cropProperties.get('initialized')) {
						var previewImageCoordinates = that._convertCropOptionsToPreviewImageCoordinates(cropOptions);

						settings.setSelect = [
							previewImageCoordinates.x,
							previewImageCoordinates.y,
							previewImageCoordinates.x + previewImageCoordinates.w,
							previewImageCoordinates.y + previewImageCoordinates.h
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
			if (!this._previewImageUri) return;

			var previewCropOptions = this._convertCropOptionsToPreviewImageCoordinates(this.get('_cropOptions'));

			var scalingFactorX = this.imagePreviewMaximumDimensions.width / previewCropOptions.w;
			var scalingFactorY = this.imagePreviewMaximumDimensions.height / previewCropOptions.h;
			var overallScalingFactor = Math.min(scalingFactorX, scalingFactorY);

			var previewBoundingBoxSize = {
				width: Math.floor(previewCropOptions.w * overallScalingFactor),
				height: Math.floor(previewCropOptions.h * overallScalingFactor)
			};

				// Update size of preview bounding box
				// and Center preview image thumbnail
			this.$().find('.typo3-imagethumbnail-inner').css({
				width: previewBoundingBoxSize.width + 'px',
				height: previewBoundingBoxSize.height + 'px',
				position: 'absolute',
				left: ((this.imagePreviewMaximumDimensions.width - previewBoundingBoxSize.width) / 2 ) + 'px',
				top: ((this.imagePreviewMaximumDimensions.height - previewBoundingBoxSize.height) / 2) + 'px'
			});

				// Scale Preview image and update relative image position
			this.$().find('.typo3-imagethumbnail-inner img').css({
				width: Math.floor(this.get('_previewImageSize').width * overallScalingFactor) + 'px',
				height: 'auto',
				marginLeft: '-' + (previewCropOptions.x * overallScalingFactor) + 'px',
				marginTop: '-' + (previewCropOptions.y * overallScalingFactor) + 'px'
			});
		}.observes('_cropProperties.x', '_cropProperties.y', '_cropProperties.width', '_cropProperties.height', '_previewImageUri'),

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
				x: previewImageCoordinates.x * (originalImageSize.width / previewImageSize.width),
				y: previewImageCoordinates.y * (originalImageSize.height / previewImageSize.height),
				width: previewImageCoordinates.w * (originalImageSize.width / previewImageSize.width),
				height: previewImageCoordinates.h * (originalImageSize.height / previewImageSize.height)
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

			if (!previewImageSize || !originalImageSize) {
				return {x: 0, y: 0, w: 1, h: 1};
			}

			if (coordinates) {
				return {
					x: parseInt(coordinates.x / (originalImageSize.width / previewImageSize.width)),
					y: parseInt(coordinates.y / (originalImageSize.height / previewImageSize.height)),
					w: parseInt(coordinates.w / (originalImageSize.width / previewImageSize.width)),
					h: parseInt(coordinates.h / (originalImageSize.height / previewImageSize.height))
				}
			} else {
				return {x: 0, y: 0, w: 0, h: 0};
			}
		},

		/**
		 * This function must be triggered *explicitely* when either:
		 * _imageUuid, _cropOptions or _scaleOptions are modified, as it
		 * writes these changes back into a JSON string.
		 *
		 * We don't use value observing here, as this might end up with a circular
		 * dependency.
		 */
		_updateValue: function() {
			this.set('value', JSON.stringify({
				originalImage: this.get('_imageUuid'),

				processingInstructions: [{
					command: 'crop',
					options: {
						start: {
							x: this._cropProperties.get('x'),
							y: this._cropProperties.get('y')
						},
						size: {
							width: this._cropProperties.get('width'),
							height: this._cropProperties.get('height')
						}
					}
				},{
					command: 'resize',
					options: {
						size: {
							width: this.get('_imageWidth'),
							height: this.get('_imageHeight')
						}
					}
				}]
			}));
		},

		/**
		 * On startup, we deserialize the JSON string and fill _imageUuid, _scaleOptions and _cropOptions
		 */
		_readAndDeserializeValue: function() {
			var imageVariant, that = this,
				value = this.get('value');

			if (value && value !== '') {
				if (T3.Common.Util.isValidJsonString(value)) {
					imageVariant = JSON.parse(value);
				}

				if (!imageVariant) return;

					// The following changes should be applied atomically
				this.beginPropertyChanges();

				this.set('_imageUuid', imageVariant.originalImage);
				$.each(imageVariant.processingInstructions, function(index, instruction) {
					if (instruction.command === 'crop') {
						var cropOptions = {
							x: Ember.getPath(instruction, 'options.start.x'),
							y: Ember.getPath(instruction, 'options.start.y'),
							w: Ember.getPath(instruction, 'options.size.width'),
							h: Ember.getPath(instruction, 'options.size.height')
						};
						that.set('_cropOptions', cropOptions);
					} else if (instruction.command === 'resize') {
						that.set('_imageWidth', Ember.getPath(instruction, 'options.size.width'));
						that.set('_imageHeight', Ember.getPath(instruction, 'options.size.height'));
					}
				});

				this.endPropertyChanges();
			}
		}.observes('value')

	});
});