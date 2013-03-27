define(
	[
		'jquery',
		'vie/instance',
		'emberjs',
		'jquery-ui',
		'aloha',
		'backbone',
		'underscorejs',
		'hallo',
		'halloplugins/linkplugin',
		'Library/createjs/deps/jquery.tagsinput.min',
		'Library/createjs/deps/rangy-core-1.2.3',
		'createjs',
		'create/collectionWidgets/jquery.typo3.collectionWidget',
		'create/typo3Notifications',
		'create/typo3MidgardEditable',
		'neos/content/model'
	],
	function($, vieInstance, Ember) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('create');

		return Ember.Object.create({
				// Initially set state to null
			_state: null,

			initialize: function() {
				var that = this;

				if (!T3.Content.Controller.Preview.get('previewMode')) {
						// Wait until Aloha is loaded if we use Aloha
					if (Aloha.ready) {
						Aloha.ready(function() {
							that.enableEdit();
						});
					} else {
						this.enableEdit();
					}
				}

				this.initializeEntitySelection();
			},

			enableEdit: function() {
				var editableOptions = {
					disabled: false,
					vie: vieInstance,
					widgets: {
						'default': 'hallo',
							// TODO Pull this from the node type definition
						'typo3:title': 'hallo-blockonly'
					},
					collectionWidgets: {
						'default': 'typo3CollectionWidget'
					},
					editors: {
						aloha: {
							widget: 'alohaWidget'
						},
						hallo: {
							widget: 'halloWidget',
							options: {
								toolbar: 'halloToolbarFixed',
								parentElement: 'body',
								toolbarCssClass: 't3-ui',
								buttonCssClass: 'btn btn-mini',
								plugins: {
									halloformat: {
										formattings: {bold: true, italic: true, strikeThrough: true, underline: true}
									},
									hallojustify: {},
									halloblock: {
										elements: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'pre', 'blockquote', 'address']
									},
									hallolists: {},
									'hallo-linkplugin': {},
									halloreundo: {}
								}
							}
						},
						'hallo-blockonly': {
							widget: 'halloWidget',
							options: {
								toolbar: 'halloToolbarFixed',
								parentElement: 'body',
								toolbarCssClass: 't3-ui',
								buttonCssClass: 'btn btn-mini',
								plugins: {
									halloblock: {
										elements: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']
									},
									'hallo-linkplugin': {},
									halloreundo: {},
									halloblacklist: {
										tags: ['br']
									}
								}
							}
						},
						'inline-only': {
							widget: 'editWidget'
						}
					}
				};

				$('[about]').each(function() {
					$(this).midgardEditable(editableOptions);
				});

				this.set('_state', 'edit');
			},

			disableEdit: function() {
				var editableOptions = {
					disabled: true,
					vie: vieInstance
				};
				$('.t3-contentelement[about]').each(function() {
					$(this).midgardEditable(editableOptions);
					$(this).removeClass('ui-state-disabled');
				});
				this.set('_state', 'browse');
			},

			/**
			 * Register a delegate for handling
			 * - disable selection if in browse (preview) mode
			 * - set currently selected entity
			 */
			initializeEntitySelection: function() {
				var that = this;
				$(document)
					.on('mouseover', 'body:not(.t3-ui-previewmode) .t3-contentelement', function(e) {
						if (e.result !== 'hovered') {
							$(this).addClass('t3-contentelement-hover');
						}
						return 'hovered';
					})
					.on('mouseout', 'body:not(.t3-ui-previewmode) .t3-contentelement', function() {
						$(this).removeClass('t3-contentelement-hover');
					})
					.on('click', '.t3-ui, .ui-widget-overlay', function(e) {
							// Stop propagation if a click was issued somewhere in a .t3-ui element
						e.stopPropagation();
						// TODO Test if we can use e.result for stopping unselect, too
					})
					.on('click', 'body:not(.t3-ui-previewmode) .t3-contentelement', function(e) {
							// Don't unselect if a previous handler activated an element
						if (e.result !== 'activated') {
							T3.Content.Model.NodeSelection.updateSelection($(this));
						}
						return 'activated';
					})
					.on('click', function(e) {
							// Don't unselect if a previous handler activated an element
						if (e.result === 'activated') {
							return;
						}
						if ($(e.target).parents().length === 0) {
								// BUGFIX for working together with DynaTree:
								// Somehow, when clicking on a non-leaf node in the tree,
								// DynaTree replaces the clicked element with a new DOM element.
								// Thus, the event target is not connected to the page anymore.
								// Thus, the stopPropagation() of t3-ui is never called; effectively
								// unselecting the current node.
							return;
						}

							// Unselect any other active element
						if (T3.Content.Model.NodeSelection.get('selectedNode') !== null) {
							T3.Content.Model.NodeSelection.updateSelection();
						}
					});
			}
		});
	}
);
