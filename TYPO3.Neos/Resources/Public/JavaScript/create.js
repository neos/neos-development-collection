define(
	[
		'Library/jquery-with-dependencies',
		'vie/instance',
		'emberjs',
		'Library/create',
		'Library/hallo',
		'halloplugins/linkplugin',
		'create/collectionWidgets/jquery.typo3.collectionWidget',
		'aloha'
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
					if (Aloha.__shouldInit) {
						// very bad workaround for initializing aloha.
						window.setTimeout(function() {
							Aloha.ready(function() {
								that.enableEdit();
							});
						}, 300);
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
						'default': 'aloha',
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
									// TODO: make plugin integration compatible with hallo on jquery UI 1.10.3
									// 'hallo-linkplugin': {},
									halloreundo: {}
								}
							}
						},
						'hallo-blockonly': {
							widget: 'halloWidget',
							options: {
								toolbar: 'halloToolbarFixed',
								parentElement: '#neos-application',
								buttonCssClass: 'btn btn-mini',
								plugins: {
									halloblock: {
										elements: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']
									},
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
				$('.neos-contentelement[about]').each(function() {
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
					.on('mouseover', 'body:not(.neos-previewmode) .neos-contentelement', function(e) {
						if (e.result !== 'hovered') {
							$(this).addClass('neos-contentelement-hover');
						}
						return 'hovered';
					})
					.on('mouseout', 'body:not(.neos-previewmode) .neos-contentelement', function() {
						$(this).removeClass('neos-contentelement-hover');
					})
					.on('click', '.neos, .ui-widget-overlay', function(e) {
							// Stop propagation if a click was issued somewhere in a .neos element
						e.stopPropagation();
						// TODO Test if we can use e.result for stopping unselect, too
					})
					.on('click', 'body:not(.neos-previewmode) .neos-contentelement', function(e) {
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
								// Thus, the stopPropagation() of neos is never called; effectively
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
