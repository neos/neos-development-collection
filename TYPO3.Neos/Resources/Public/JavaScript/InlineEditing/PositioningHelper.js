define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Content/Model/NodeSelection'
	],
	function($, Ember, NodeSelection) {

		return Ember.Object.create({

			nodeSelection: NodeSelection,

			init: function() {
				var that = this;

				setInterval(function() {
					that.updateContentElementHandlePosition();
					that.updateNotInlineEditableOverlay();
				}, 100);
			},

			/**
			 * Sets the width of the content element overlay which is
			 * added to not inline editable elements
			 *
			 * @return {void}
			 */
			updateNotInlineEditableOverlay: function() {
				$('.neos-contentelement-overlay').each(function() {
						$(this).css({
							'width': $(this).parent().outerWidth(),
							'height': $(this).parent().outerHeight()
						});
				});
			},

			/**
			 * Updates the position of the InlineEditingHandles
			 *
			 * @return {void}
			 */
			updateContentElementHandlePosition: function() {
				if (!this.get('nodeSelection.selectedNode') || !this.get('nodeSelection.selectedNode').$element || $('.neos-handle-container:visible').length === 0) {
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

				positioning.call(this, $('.neos-handle-container').eq(0), this.get('nodeSelection.selectedNode').$element);
			}
		});
	}
);