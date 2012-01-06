/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'text!phoenix/templates/content/ui/fileUpload.html',
	'text!phoenix/templates/content/ui/imageUpload.html'
],
function(fileUploadTemplate, imageUploadTemplate) {
	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}
	T3.Content.UI = T3.Content.UI || {};
	T3.Content.UI.Editor = T3.Content.UI.Editor || {};
	var $ = window.Aloha.jQuery || window.jQuery;

	T3.Content.UI.Editor.TextField = SC.TextField.extend({
	});

	T3.Content.UI.Editor.Checkbox = SC.Checkbox.extend({
	});

	T3.Content.UI.Editor.DateField = SC.TextField.extend({
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

	T3.Content.UI.Editor.SelectboxOption = SC.View.extend({
		tagName: 'option',
		attributeBindings: ['value', 'selected'],
		valueBinding: 'content.value',
		selectedBinding: 'content.selected',

		template: SC.Handlebars.compile('{{content.label}}')
	});

	T3.Content.UI.Editor.Selectbox = SC.CollectionView.extend({
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
				options.push(SC.Object.create({value: '', label: this.get('placeholder')}));
			}
			$.each(this.get('values'), function(value) {
				options.push(SC.Object.create($.extend({
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
					'Library/chosen/chosen/chosen.jquery.min',
					'css!Library/chosen/chosen/chosen.css'
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

		onPopoverOpen: function() {
			var that = this,
				id = this.get(SC.GUID_KEY);

				// Initialize CodeMirror editor with a nice html5 canvas demo.
			if (!this._editorInitialized) {

				var $editorContent = $('<textarea />', {
					id: 'typo3-htmleditor-' + id
				}).html(that.get('value'));

				this.$popoverContent.append($editorContent);

				require([
					'order!Library/codemirror2/lib/codemirror',
					'order!Library/codemirror2/mode/xml/xml',
					'order!Library/codemirror2/mode/css/css',
					'order!Library/codemirror2/mode/javascript/javascript',
					'order!Library/codemirror2/mode/htmlmixed/htmlmixed',

					'css!Library/codemirror2/lib/codemirror.css',
					'css!Library/codemirror2/theme/default.css'
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
				$('#typo3-htmleditor-' + this.get(SC.GUID_KEY)).remove();
				this._editorInitialized = false;
			}
			// TODO: not only hide the popover, but completely remove it from DOM!
		}

	});

	T3.Content.UI.Editor.FileUpload = SC.View.extend({

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

		template: SC.Handlebars.compile(fileUploadTemplate),

		init: function() {
			var id = this.get(SC.GUID_KEY);
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

		template: SC.Handlebars.compile(imageUploadTemplate),

		// Upload Preview
		_uploadPreviewShown: true,
		_uploadPreviewNotShown: function() {
			return !this.get('_uploadPreviewShown');
		}.property('_uploadPreviewShown'),

		_uploadPreviewImageSource: '/_Resources/Static/Packages/TYPO3.TYPO3/Images/dummy-image.jpg', // TODO: we need a way to fetch the static resources base URI

		// Cropping
		_originalImageSize: null,
		_previewImageSize: null,
		_previewImageUri: null,

		_imageUuid: null,

		// Transformation options
		_cropOptions: null,

		// Image Badge
		_imageBadge: null,

		_imageBadgeChange: function() {
			if (this.get('_imageBadge')) {
				this.$().find('.typo3-imagebadge').addClass('typo3-imagebadge-visible');
			} else {
				this.$().find('.typo3-imagebadge').removeClass('typo3-imagebadge-visible');
			}
		}.observes('_imageBadge'),

		/**
		 * Lifecycle callback; sets some CSS for the image preview area to sensible defaults.
		 */
		didInsertElement: function() {
			var that = this;
			this._super();

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
							that.set('_imageBadge', null);
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
			this.set('_cropOptions', {
				x:0,
				y:0,
				w:responseJson.originalSize.width,
				h:responseJson.originalSize.height
			})
			this._setPreviewImage(responseJson);
			this._updateValue();
		},

		_setPreviewImage: function(responseJson) {
			SC.beginPropertyChanges();

			this.set('_originalImageSize', responseJson.originalSize);
			this.set('_previewImageSize', responseJson.previewSize);
			this.set('_previewImageUri', responseJson.previewImageResourceUri);
			this.set('_uploadPreviewShown', false);
			this.set('_imageBadge', 'Click to Crop');

			SC.endPropertyChanges();
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
							that.set('_cropOptions', that._convertCropOptionsFromPreviewImageCoordinates(previewImageCoordinates));
							that._updateValue();
						}
					};

						// If we have all crop options set, we preselect this in the cropping tool.
					if (cropOptions) {
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
			var overalScalingFactor = Math.min(scalingFactorX, scalingFactorY);

			var previewBoundingBoxSize = {
				width: Math.floor(previewCropOptions.w * overalScalingFactor),
				height: Math.floor(previewCropOptions.h * overalScalingFactor)
			}

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
				width: Math.floor(this.get('_previewImageSize').width * overalScalingFactor) + 'px',
				height: 'auto',
				marginLeft: '-' + (previewCropOptions.x * overalScalingFactor) + 'px',
				marginTop: '-' + (previewCropOptions.y * overalScalingFactor) + 'px'
			});
		}.observes('_cropOptions', '_previewImageUri'),

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
				w: previewImageCoordinates.w * (originalImageSize.width / previewImageSize.width),
				h: previewImageCoordinates.h * (originalImageSize.height / previewImageSize.height)
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
					x: coordinates.x / (originalImageSize.width / previewImageSize.width),
					y: coordinates.y / (originalImageSize.height / previewImageSize.height),
					w: coordinates.w / (originalImageSize.width / previewImageSize.width),
					h: coordinates.h / (originalImageSize.height / previewImageSize.height)
				}
			} else {
				return {x:0,y:0,w:0,h:0};
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
			var cropOptions = this.get('_cropOptions');

			this.set('value', JSON.stringify({
				originalImage: this.get('_imageUuid'),

				processingInstructions: [{
					command: 'crop',
					options: {
						start: {
							x: cropOptions.x,
							y: cropOptions.y
						},
						size: {
							width: cropOptions.w,
							height: cropOptions.h
						}
					}
				}/*,{
					command: 'resize',
					options: this.get('_scaleOptions')
				}*/] // TODO: Implement resizing
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
							x: SC.getPath(instruction, 'options.start.x'),
							y: SC.getPath(instruction, 'options.start.y'),
							w: SC.getPath(instruction, 'options.size.width'),
							h: SC.getPath(instruction, 'options.size.height')
						};
						that.set('_cropOptions', cropOptions)
					} else if (instruction.command === 'resize') {
						that.set('_scaleOptions', instruction.options)
					}
				});

				this.endPropertyChanges();
			}
		}.observes('value')

	});
});