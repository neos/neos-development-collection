/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'phoenix/fixture',
	'text!phoenix/content/ui/toolbar.html',
	'text!phoenix/content/ui/breadcrumb.html',
	'text!phoenix/content/ui/inspector.html',
	'text!phoenix/content/ui/inspectordialog.html',
	'text!phoenix/content/ui/fileupload.html',
	'text!phoenix/content/ui/imageupload.html',
	'Library/jquery-popover/jquery.popover',
	'Library/jquery-notice/jquery.notice',
	'css!Library/jquery-notice/jquery.notice.css',
	'Library/jcrop/js/jquery.Jcrop.min',
	'css!Library/jcrop/css/jquery.Jcrop.css',
	'order!Library/plupload/js/plupload',
	'order!Library/plupload/js/plupload.html5'
],
function(fixture, toolbarTemplate, breadcrumbTemplate, inspectorTemplate, inspectordialogTemplate, fileUploadTemplate, imageUploadTemplate) {
	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};
	var $ = window.alohaQuery || window.jQuery;


	/**
	 * ===========================
	 * SECTION: SIMPLE UI ELEMENTS
	 * ===========================
	 * - Toolbar
	 * - Button
	 * - ToggleButton
	 * - PopoverButton
	 */

	/**
	 * T3.Content.UI.Toolbar
	 *
	 * Toolbar which can contain other views. Has two areas, left and right.
	 */
	var Toolbar = SC.View.extend({
		tagName: 'div',
		classNames: ['t3-toolbar', 'aloha-block-do-not-deactivate'],
		template: SC.Handlebars.compile(toolbarTemplate)
	});

	/**
	 * T3.Content.UI.Button
	 *
	 * A simple, styled TYPO3 button.
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	var Button = SC.Button.extend({
		classNames: ['t3-button'],
		attributeBindings: ['disabled'],
		classNameBindings: ['iconClass'],
		label: '',
		disabled: false,
		visible: true,
		icon: '',
		template: SC.Handlebars.compile('{{label}}'),
		iconClass: function() {
			var icon = this.get('icon');
			return icon !== '' ? 't3-icon-' + icon : '';
		}.property('icon').cacheable()
	});

	var Image = SC.View.extend({
		tagName: 'img',
		attributeBindings: ['src']
	});

	/**
	 * T3.Content.UI.ToggleButton
	 *
	 * A button which has a "pressed" state
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	var ToggleButton = Button.extend({
		classNames: ['t3-button'],
		classNameBindings: ['pressed'],
		pressed: false,
		toggle: function() {
			this.set('pressed', !this.get('pressed'));
		},
		mouseUp: function(event) {
			if (this.get('isActive')) {
				var action = this.get('action'),
				target = this.get('targetObject');

				this.toggle();
				if (target && action) {
					if (typeof action === 'string') {
						action = target[action];
					}
					action.call(target, this.get('pressed'), this);
				}

				this.set('isActive', false);
			}

			this._mouseDown = false;
			this._mouseEntered = false;
		}
	});

	/**
	 * T3.Content.UI.PopoverButton
	 *
	 * A button which, when pressed, shows a "popover". You will subclass
	 * this class and implement onPopoverOpen / popoverTitle / $popoverContent
	 */
	var PopoverButton = ToggleButton.extend({

		/**
		 * @var {String} title of the popover
		 */
		popoverTitle: '',

		/**
		 * @var {jQuery} content of the popover. to be manipulated in the onPopoverOpen function
		 */
		$popoverContent: $('<div></div>'),

		/**
		 * @var {String} one of "top, bottom, left, right". Specifies the popover position.
		 */
		popoverPosition: 'bottom',

		/**
		 * Lifecycle method by SproutCore, executed as soon as the element has been
		 * inserted in the DOM and the $() method is executable. We initialize the
		 * popover at this point.
		 */
		didInsertElement: function() {
			var that = this;
			this.$().popover({
				header: $('<div>' + that.get('popoverTitle') + '</div>'),
				content: that.$popoverContent,
				preventLeft: (that.get('popoverPosition')==='left' ? false : true),
				preventRight: (that.get('popoverPosition')==='right' ? false : true),
				preventTop: (that.get('popoverPosition')==='top' ? false : true),
				preventBottom: (that.get('popoverPosition')==='bottom' ? false : true),
				zindex: 10090,
				closeEvent: function() {
					that.set('pressed', false);
				},
				openEvent: function() {
					that.onPopoverOpen.call(that);
				}
			});
		},

		/**
		 * Template method, to be implemented in subclasses. Usually,
		 * you want to manipulate this.$popoverContent in this method.
		 */
		onPopoverOpen: function() {
		}
	});

	/**
	 * =====================
	 * SECTION: UI CONTAINRS
	 * =====================
	 * - Breadcrumb
	 * - BreadcrumbItem
	 * - Inspector
	 */

	/**
	 * T3.Content.UI.Breadcrumb
	 *
	 * The breadcrumb menu
	 */
	var Breadcrumb = SC.View.extend({
		tagName: 'div',
		classNames: ['t3-breadcrumb'],
		template: SC.Handlebars.compile(breadcrumbTemplate)
	});

	/**
	 * T3.Content.UI.BreadcrumbItem
	 *
	 * view for a single breadcrumb item
	 * @internal
	 */
	var BreadcrumbItem = SC.View.extend({
		tagName: 'a',
		href: '#',

		// TODO Don't need to bind here actually
		attributeBindings: ['href'],
		template: SC.Handlebars.compile('{{item.__titleAndModifcationState}}'),
		click: function(event) {
			var item = this.get('item');
			T3.Content.Model.BlockSelection.selectItem(item);
			event.stopPropagation();
			return false;
		}
	});



	/**
	 * T3.Content.UI.Inspector
	 *
	 * The Inspector is displayed on the right side of the page.
	 *
	 * Furthermore, it contains *Editors*
	 */
	var Inspector = SC.View.extend({
		template: SC.Handlebars.compile(inspectorTemplate),

		/**
		 * When we are in edit mode, the click protection layer is intercepting
		 * every click outside the Inspector.
		 */
		$clickProtectionLayer: null,

		/**
		 * When pressing Enter inside a property, we apply and leave the edit mode
		 */
		keyDown: function(event) {
			if (event.keyCode === 13) {
				T3.Content.Controller.Inspector.apply();
				return false;
			}
		},

		/**
		 * When the editors have been modified, we add / remove the click
		 * protection layer.
		 */
		_onModifiedChange: function() {
			var zIndex;
			if (T3.Content.Controller.Inspector.get('_modified')) {
				zIndex = this.$().css('z-index') - 1;
				this.$clickProtectionLayer = $('<div />').addClass('t3-inspector-clickprotection').addClass('aloha-block-do-not-deactivate').css({'z-index': zIndex});
				this.$clickProtectionLayer.click(this._showUnappliedDialog);
				$('body').append(this.$clickProtectionLayer);
			} else {
				this.$clickProtectionLayer.remove();
			}
		}.observes('T3.Content.Controller.Inspector._modified'),

		/**
		 * When clicking the click protection, we show a dialog
		 */
		_showUnappliedDialog: function() {
			var view = SC.View.create({
				template: SC.Handlebars.compile(inspectordialogTemplate),
				didInsertElement: function() {
					var title = this.$().find('h1').remove().html();

					this.$().dialog({
						modal: true,
						zIndex: 11001,
						title: title,
						close: function() {
							view.destroy();
						}
					});
				},
				cancel: function() {
					this.$().dialog('close');
				},
				apply: function() {
					T3.Content.Controller.Inspector.apply();
					this.$().dialog('close');
				},
				dontApply: function() {
					T3.Content.Controller.Inspector.revert();
					this.$().dialog('close');
				}
			});
			view.append();
		}
	});

	Inspector.PropertyEditor = SC.ContainerView.extend({
		propertyDefinition: null,

		render: function() {
			var typeDefinition = T3.Configuration.UserInterface[this.propertyDefinition.type];
			if (!typeDefinition) {
				throw {message: 'Type defaults for "' + this.propertyDefinition.type + '" not found', code: 1316346119};
			}

			var editorConfigurationDefinition = typeDefinition;
			if (this.propertyDefinition.userInterface && this.propertyDefinition.userInterface) {
				editorConfigurationDefinition = $.extend({}, editorConfigurationDefinition, this.propertyDefinition.userInterface);
			}

			var editorClass = SC.getPath(editorConfigurationDefinition['class']);
			if (!editorClass) {
				throw 'Editor class "' + typeDefinition['class'] + '" not found';
			}

			var classOptions = $.extend({
				valueBinding: 'T3.Content.Controller.Inspector.blockProperties.' + this.propertyDefinition.key

			}, this.propertyDefinition.options || {});
			classOptions = $.extend(classOptions, typeDefinition.options || {});

			var editor = editorClass.create(classOptions);
			this.appendChild(editor);
			this._super();
		}
	});

	var Editor = {};
	Editor.TextField = SC.TextField.extend({
	});

	Editor.Checkbox = SC.Checkbox.extend({
	});

	Editor.DateField = SC.TextField.extend({
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

	Editor.SelectboxOption = SC.View.extend({
		tagName: 'option',
		attributeBindings: ['value', 'selected'],
		valueBinding: 'content.value',
		selectedBinding: 'content.selected',

		template: SC.Handlebars.compile('{{content.label}}')
	});

	Editor.Selectbox = SC.CollectionView.extend({
		classNames: ['typo3-form-selectbox'],

		tagName: 'select',
		contentBinding: 'options',
		itemViewClass: Editor.SelectboxOption,

		value: '',
		allowEmpty: false,
		placeholder: '',

		attributeBindings: ['size', 'disabled'],

		items: [],

		options: function() {
			var options = [], currentValue = this.get('value');
			if (this.get('allowEmpty')) {
				options.push(SC.Object.create({value: '', label: this.get('placeholder')}));
			}
			$.each(this.get('items'), function() {
				options.push(SC.Object.create($.extend({selected: this.value === currentValue}, this)));
			});
			return options;
		}.property('items', 'value', 'placeholder', 'allowEmpty').cacheable(),

		onItemsChange: function() {
			// Special event for chosen
			this.$().trigger("liszt:updated");
		}.observes('items'),

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

	Editor.HtmlEditor = PopoverButton.extend({

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
					'css!Library/codemirror2/theme/default.css',
				], function() {
					that._editor = CodeMirror.fromTextArea($editorContent.get(0), {
						mode: 'text/html',
						tabMode: 'indent',
						lineNumbers: true,
						onChange: function() {
							if (that._editor) {
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

	Editor.FileUpload = SC.View.extend({

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
	Editor.Image = Editor.FileUpload.extend({

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
							previewImageCoordinates.y + previewImageCoordinates.h,
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
					// HACK: we need to convert *invalid* JSON to valid one again as
					// the Chrome browser seems to convert it to an object, and in turn
					// then serialize it to string again... at least unter some
					// circumstances... Funny :-)
				if (value.substr(0, 4) === 'HACK') {
					value = value.substr(4);
				}

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

	/**
	 * ==================
	 * SECTION: PAGE TREE
	 * ==================
	 * - PageTreeLoader
	 * - PageTreeButton
	 */
	var PageTreeButton = PopoverButton.extend({
		$popoverContent: $('<div class="extjs-container"></div>'),

		/**
		 * @var {Ext.tree.TreePanel} Reference to the ExtJS tree; or null if not yet built.
		 */
		_tree: null,

		onPopoverOpen: function() {
			if (this._tree) return;

			this._tree = new Ext.tree.TreePanel({
				width:250,
				height:350,
				useArrows: true,
				autoScroll: true,
				animate: true,
				enableDD: true,
				border: false,
				ddGroup: 'pages',

				root: {
					id: $('#t3-page-metainformation').data('__siteroot'), // TODO: This and the following properties might later come from the SproutCore model...
					text: $('#t3-page-metainformation').data('__sitename'),
					draggable: false
				},

				loader: new Ext.tree.TreeLoader({
					/**
					 * Wrapper for extDirect call to NodeController which
					 * adds the child node type to the extDirect call as 2nd parameter.
					 *
					 * @param {String} contextNodePath the current Context Node Path to get subnodes from
					 * @param {Function} callback function after request is done
					 * @return {void}
					 */
					directFn: function(contextNodePath, callback) {
						TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(contextNodePath, 'TYPO3.TYPO3:Page', callback);
					},

					/**
					 * Here, we convert the response back to a format ExtJS understands; namely we use result.data instead of result here.
					 *
					 * @param {Object} result the result part from the response of the server request
					 * @param {Object} response the response object of the server request
					 * @param {Object} args request arguments passed through
					 * @return {void}
					 */
					processDirectResponse: function(result, response, args) {
						if (response.status) {
							this.handleResponse({
								responseData: Ext.isArray(result.data) ? result.data : null,
								responseText: result,
								argument: args
							});
						} else {
							this.handleFailure({
								argument: args
							});
						}
					}
				}),

				listeners: {
					click: this._onTreeNodeClick,
					movenode: this._onTreeNodeMove,
					beforenodedrop: this._onTreeNodeDrop
				}
			});

			this._initNewPageDraggable();

			var $treeContainer = $('<div />');
			this.$popoverContent.append($treeContainer);

			this._tree.render($treeContainer[0]);
			this._tree.getRootNode().expand();
		},

		/**
		 * Initializer for the "new page" draggable, creating an element
		 * and a Drag Zone.
		 */
		_initNewPageDraggable: function() {
			var $newPageDraggable = $('<div class="t3-dd-newpage">New page</div>');
			this.$popoverContent.append($newPageDraggable);

			new Ext.dd.DragZone($newPageDraggable[0], {
				ddGroup: 'pages',

				getDragData: function(event) {
					this.proxyElement = document.createElement('div');

					return {
						ddel: this.proxyElement,
						mode: 'new'
					}
				},

				onInitDrag: function() {
					this.proxyElement.shadow = false;
					this.proxyElement.innerHTML = '<div class="t3-dd-drag-ghost-pagetree">' +
						'Insert Page here' +
					'</div>';

					this.proxy.update(this.proxyElement);
				}
			});
		},

		/**
		 * Callback which is executed when a TreeNode is clicked.
		 *
		 * @param {Ext.tree.TreeNode} node
		 * @param {Object} event
		 */
		_onTreeNodeClick: function(node, event) {
				// TODO: clean this up, so that clicking the "GOTO" link works without this click hack; or built some different way of handling this case.
			if ($(event.getTarget()).is('a.t3-gotoPage')) {
				T3.ContentModule.loadPage($(event.getTarget()).attr('href'));
			};
		},

		/**
		 * Callback which is executed when a TreeNode is moved to an other TreeNode.
		 */
		_onTreeNodeMove: function() {
			// TODO: implement
		},

		/**
		 * Callback, executed when something is dropped on the tree. We insert
		 * an element in case the newPageDraggable is dropped on the tree.
		 *
		 * @param {Object} event
		 */
		_onTreeNodeDrop: function(event) {
			if (event.data.mode === 'new') {
				var position = 0;
				if (event.point === 'above') {
					position = -1;
				} else if (event.point === 'below') {
					position = 1;
				}

				TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.create(
					event.target.attributes.id,
					{
						contentType: 'TYPO3.TYPO3:Page',
						properties: {
							title: '[New Page]'
						}
					},
					position,
					function() {
						event.target.parentNode.reload();
					}
				);
			}
		}
	});

	var InspectButton = PopoverButton.extend({
		$popoverContent: $('<div class="extjs-container" style="height: 350px"></div>'),

		popoverPosition: 'top',

		/**
		 * @var {Ext.tree.TreePanel} Reference to the ExtJS tree; or null if not yet built.
		 */
		_tree: null,

		onPopoverOpen: function() {
			if (this._tree) return;

			this._tree = new Ext.tree.TreePanel({
				width:250,
				height:350,
				useArrows: true,
				autoScroll: true,
				animate: true,
				enableDD: true,
				border: false,
				ddGroup: 'nodes',

				root: {
					id: $('#t3-page-metainformation').attr('data-__nodepath'), // TODO: This and the following properties might later come from the SproutCore model...
					text: $('#t3-page-metainformation').data('title'),
					draggable: false
				},

				loader: new Ext.tree.TreeLoader({
					/**
					 * Wrapper for extDirect call to NodeController which
					 * adds the child node type to the extDirect call as 2nd parameter.
					 *
					 * @param {String} contextNodePath the current Context Node Path to get subnodes from
					 * @param {Function} callback function after request is done
					 * @return {void}
					 */
					directFn: function(contextNodePath, callback) {
						TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(contextNodePath, '!TYPO3.TYPO3:Page', callback);
					},

					/**
					 * Here, we convert the response back to a format ExtJS understands; namely we use result.data instead of result here.
					 *
					 * @param {Object} result the result part from the response of the server request
					 * @param {Object} response the response object of the server request
					 * @param {Object} args request arguments passed through
					 * @return {void}
					 */
					processDirectResponse: function(result, response, args) {
						if (response.status) {
							this.handleResponse({
								responseData: Ext.isArray(result.data) ? result.data : null,
								responseText: result,
								argument: args
							});
						} else {
							this.handleFailure({
								argument: args
							});
						}
					}
				})
			});

			var $treeContainer = $('<div />');
			this.$popoverContent.append($treeContainer);

			this._tree.render($treeContainer[0]);
			this._tree.getRootNode().expand();
		}
	});

	T3.Content.UI = {
		Toolbar: Toolbar,
		Button: Button,
		ToggleButton: ToggleButton,
		PopoverButton: PopoverButton,
		PageTreeButton: PageTreeButton,
		InspectButton: InspectButton,
		Breadcrumb: Breadcrumb,
		BreadcrumbItem: BreadcrumbItem,
		Inspector: Inspector,
		Editor: Editor,
		Image: Image
	};
});
