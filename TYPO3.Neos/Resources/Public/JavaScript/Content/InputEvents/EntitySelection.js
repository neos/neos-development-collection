define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Content/Model/NodeSelection'
	],
	function(
		$,
		Ember,
		NodeSelection
	) {
		return Ember.Object.create({

			/**
			 * Register a delegate for handling
			 * - disable selection if in browse (preview) mode
			 * - set currently selected entity
			 */
			initialize: function() {
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
							NodeSelection.updateSelection($(this));
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
						if (NodeSelection.get('selectedNode') !== null) {
							NodeSelection.updateSelection();
						}
						return false;
					});
			}
		});
	}
);