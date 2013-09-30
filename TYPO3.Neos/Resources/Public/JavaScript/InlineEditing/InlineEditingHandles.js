define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Content/Model/NodeSelection',
		'InlineEditing/InlineEditingHandles/ContentElementHandle',
		'InlineEditing/InlineEditingHandles/SectionHandle'
	],
	function(
		$,
		Ember,
		NodeSelection,
		ContentElementHandle,
		SectionHandle
	) {
		return Ember.View.extend({
			classNameBindings: ['isPage:neos-hide'],
			classNames: ['neos-handle-container'],
			template: Ember.Handlebars.compile(
				'{{view view.ContentElementHandle isVisibleBinding="view.isContentElementBar"}}' +
				'{{view view.SectionHandle isVisibleBinding="view.isSectionBar"}}'
			),

			nodeSelection: NodeSelection,

			// Register views
			ContentElementHandle: ContentElementHandle,
			SectionHandle: SectionHandle,

			/**
			 * Returns true if the selected node is page
			 *
			 * @return {boolean}
			 */
			isPage: function() {
				return this.get('_selectedNode') && this.get('_selectedNode').get('nodeType') === 'TYPO3.Neos:Page';
			}.property('_selectedNode'),

			/**
			 * Returns true if the selected node is not a section.
			 * This method does not take pages into account as the full bar is hidden if the node is a page
			 *
			 * @return {boolean}
			 */
			isContentElementBar: function() {
				return this.get('_selectedNode') && this.get('_selectedNode').get('nodeType') !== 'TYPO3.Neos:ContentCollection';
			}.property('_selectedNode'),

			/**
			 * Returns true if the selected node is a section
			 *
			 * @return {boolean}
			 */
			isSectionBar: function() {
				return this.get('_selectedNode') && this.get('_selectedNode').get('nodeType') === 'TYPO3.Neos:ContentCollection';
			}.property('_selectedNode'),

			/**
			 * Returns the current selected node in the NodeSelection.
			 *
			 * @return {Entity}
			 */
			_selectedNode: function() {
				return NodeSelection.get('selectedNode');
			}.property('nodeSelection.selectedNode'),

			/**
			 * Updates the position of the InlineEditingHandles
			 * FIXME: Fix positioning on viewport change (window resize, inspector collapse, and so on)
			 *
			 * @return {void}
			 */
			updatePosition: function() {
				if (!this.$() || !this.get('_selectedNode').$element) {
					return;
				}

				var positioning = function($handle, $node) {
					$handle.position({
						of: $node,
						my: 'right top',
						at: 'right top',
						using: function(position, elements) {
							if (elements.target.element) {
								var $target = elements.target.element;
								if ($('body').css('position') === 'relative') {
									position.top = $target.offset().top - $('body').offset().top - elements.element.element.height() + parseInt($target.css('outline-width')) + 1;
								} else {
									position.top = $target.offset().top - elements.element.element.height() + parseInt($target.css('outline-width')) + 1;
								}

								elements.element.element.css(position);
							}
						}
					});
				};

				positioning.call(this, this.$(), this.get('_selectedNode').$element);
			}.observes('_selectedNode')
		});
	}
);