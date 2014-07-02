define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Content/Model/NodeSelection',
		'LibraryExtensions/Mousetrap'
	],
	function(
		$,
		Ember,
		NodeSelection,
		Mousetrap
	) {
		return Ember.Object.create({

			_neosHasFocus: false,

			/**
			 * Register a delegate for handling
			 * - disable selection if in browse (preview) mode
			 * - set currently selected entity
			 */
			initialize: function() {
				var that = this;

				$('#neos-application')
					.on('focusout', function() {
						that.set('_neosHasFocus', false);
					})
					.on('focusin', function() {
						that.set('_neosHasFocus', true);
					});

				$(document)
					.on('mouseover', 'body:not(.neos-preview-mode) .neos-contentelement', function(e) {
						if (e.result !== 'hovered') {
							$(this).addClass('neos-contentelement-hover');
						}
						return 'hovered';
					})
					.on('mouseout', 'body:not(.neos-preview-mode) .neos-contentelement', function() {
						$(this).removeClass('neos-contentelement-hover');
					})
					.on('click', '.neos, .ui-widget-overlay', function(e) {
						// Stop propagation if a click was issued somewhere in a .neos element
						e.stopPropagation();
						// TODO Test if we can use e.result for stopping deselect, too
					})
					.on('click', 'body:not(.neos-preview-mode) .neos-contentelement', function(e) {
						// Don't unselect if a previous handler activated an element
						if (e.result !== 'activated') {
							NodeSelection.updateSelection($(this));
						}
						return 'activated';
					})
					.on('click', function(e) {
						// Don't deselect if a previous handler activated an element
						if (e.result === 'activated') {
							return;
						}
						if ($(e.target).parents().length === 0 && !$(e.target).is('html')) {
							// BUGFIX for working together with DynaTree:
							// Somehow, when clicking on a non-leaf node in the tree,
							// DynaTree replaces the clicked element with a new DOM element.
							// Thus, the event target is not connected to the page anymore.
							// Thus, the stopPropagation() of Neos is never called; effectively
							// deselecting the current node.
							return;
						}
						// Deselect any other active element
						if (NodeSelection.get('selectedNode') !== null) {
							NodeSelection.updateSelection();
						}
					});

				// Keyboard events
				Mousetrap.bind('tab', function() {
					if ($('body').hasClass('neos-preview-mode')) {
						// Don't handle navigation in preview mode
						return;
					}

					// We don't want to handle node selection if the focus is in a Neos application component like the inspector
					// because this handler only handles navigation for the nodes.
					if (that.get('_neosHasFocus')) {
						return;
					}

					var contentElements = $('.neos-contentelement'),
						activeContentElementIndex = contentElements.index($('.neos-contentelement-active')),
						nextActiveContentElementIndex,
						nextActiveContentElement;

					nextActiveContentElementIndex = activeContentElementIndex + (event.shiftKey === true ? -1 : 1);
					if (nextActiveContentElementIndex < 0 || nextActiveContentElementIndex >= contentElements.length) {
						nextActiveContentElementIndex = 0;
					}
					nextActiveContentElement = $('.neos-contentelement:eq(' + nextActiveContentElementIndex + ')');

					NodeSelection.updateSelection(nextActiveContentElement);
					if (nextActiveContentElement.find('> .neos-inline-editable').length > 0) {
						nextActiveContentElement.find('> .neos-inline-editable').focus();
					}
				});
			}
		});
	}
);