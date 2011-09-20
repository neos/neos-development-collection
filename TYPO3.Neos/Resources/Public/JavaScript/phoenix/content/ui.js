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
	'Library/jquery-popover/jquery.popover',
	'Library/jquery-notice/jquery.notice',
	'css!Library/jquery-popover/jquery.popover.css',
	'css!Library/jquery-notice/jquery.notice.css',
	'Library/plupload/js/plupload.full'
],
function(fixture, toolbarTemplate, breadcrumbTemplate, inspectorTemplate, inspectordialogTemplate, fileUploadTemplate) {
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
		template: SC.Handlebars.compile('{{item._titleAndModifcationState}}'),
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
	 * Furthermore, it contains *Editors* and *Renderers*
	 */
	var Inspector = SC.View.extend({
		template: SC.Handlebars.compile(inspectorTemplate),

		/**
		 * When we are in edit mode, the click protection layer is intercepting
		 * every click outside the Inspector.
		 */
		$clickProtectionLayer: null,

		/**
		 * When pressing Enter inside a property, we save and leave the edit mode
		 */
		keyDown: function(event) {
			if (event.keyCode === 13) {
				T3.Content.Controller.Inspector.save();
				return false;
			}
		},

		doubleClick: function(event) {
			T3.Content.Controller.Inspector.set('editMode', true);
		},

		/**
		 * When the edit mode is entered or left, we add / remove the click
		 * protection layer.
		 */
		onEditModeChange: function() {
			var zIndex;
			if (T3.Content.Controller.Inspector.get('editMode')) {
				zIndex = this.$().css('z-index') - 1;
				this.$clickProtectionLayer = $('<div />').addClass('t3-inspector-clickprotection').addClass('aloha-block-do-not-deactivate').css({'z-index': zIndex});
				this.$clickProtectionLayer.click(this._showUnsavedDialog);
				$('body').append(this.$clickProtectionLayer);
			} else {
				this.$clickProtectionLayer.remove();

			}
		}.observes('T3.Content.Controller.Inspector.editMode'),

		/**
		 * When clicking the click protectiom, we show a dialog
		 */
		_showUnsavedDialog: function() {
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
				save: function() {
					T3.Content.Controller.Inspector.save();
					this.$().dialog('close');
				},
				dontSave: function() {
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

			var editorClass = SC.getPath(typeDefinition.editor['class']);
			if (!editorClass) {
				throw 'Editor class "' + typeDefinition.editor['class'] + '" not found';
			}

			var classOptions = $.extend({
				valueBinding: 'T3.Content.Controller.Inspector.blockProperties.' + this.propertyDefinition.key
			}, typeDefinition.editor.options || {});

			var editor = editorClass.create(classOptions);
			this.appendChild(editor);

			this._super();
		}
	});

	Inspector.PropertyRenderer = SC.ContainerView.extend({
		propertyDefinition: null,

		render: function() {
			var typeDefinition = T3.Configuration.UserInterface[this.propertyDefinition.type];
			if (!typeDefinition) {
				throw {message: 'Type defaults for "' + this.propertyDefinition.type + '" not found', code: 1316346119};
			}

			var rendererClass = SC.getPath(typeDefinition.renderer['class']);
			if (!rendererClass) {
				throw 'Renderer class "' + typeDefinition.renderer['class'] + '" not found';
			}

			var classOptions = $.extend({
				valueBinding: 'T3.Content.Controller.Inspector.blockProperties.' + this.propertyDefinition.key
			}, typeDefinition.renderer.options || {});

			var renderer = rendererClass.create(classOptions);
			this.appendChild(renderer);

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
			this.$().datepicker({
				dateFormat: $.datepicker.W3C,
				beforeShow: function(field, datePicker) {
					$(datePicker.dpDiv).addClass('aloha-block-do-not-deactivate');
				}
			});
		}
	});

	Editor.FileUpload = SC.View.extend({

		value: '',

		// File filters
		allowedFileTypes: null,

		_uploader: null,
		_containerId: null,
		_browseButtonId: null,

		template: SC.Handlebars.compile(fileUploadTemplate),

		init: function() {
			var id = this.get(SC.GUID_KEY);
			this._containerId = 'typo3-fileupload' + id;
			this._browseButtonId = 'typo3-fileupload-browsebutton' + id;
			this._super();
		},

		didInsertElement: function() {
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

			this._uploader.bind('BeforeUpload', function(uploader, file) {
				uploader.settings.multipart_params['image[type]'] = 'plupload';
				uploader.settings.multipart_params['image[fileName]'] = file.name;
			});

			this._uploader.bind('FileUploaded', function(uploader, file, response) {
				T3.Common.Notification.ok('Uploaded file "' + file.name + '".');
				that.set('value', response.response);
			});

			this._uploader.init();
			this._uploader.refresh();
		},
		upload: function() {
			this._uploader.start();
		}
	});

	var Renderer = {};
	Renderer.Text = SC.View.extend({
		value: '',
		template: SC.Handlebars.compile('<span style="color:white">{{value}}</span>')
	});

	Renderer.Boolean = SC.View.extend({
		value: null,
		template: SC.Handlebars.compile('<span style="color:white">{{#if value}}<span class="t3-boolean-true">Yes</span>{{/if}} {{#unless value}}<span class="t3-boolean-false">No</span>{{/unless}}</span>')
	});

	Renderer.File = SC.View.extend({
		template: SC.Handlebars.compile('{{value}}')
	});

	Renderer.Date = SC.View.extend({
		value: '',
		template: SC.Handlebars.compile('<span style="color:white">{{value}}</span>')
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
					id: $('#t3-page-metainformation').attr('about'), // TODO: This and the following properties might later come from the SproutCore model...
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
		Renderer: Renderer
	};
});

