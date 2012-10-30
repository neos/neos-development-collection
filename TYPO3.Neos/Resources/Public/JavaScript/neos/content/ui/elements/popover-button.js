/**
 * A button which, when pressed, shows a "popover". You will subclass
 * this class and implement onPopoverOpen / popoverTitle / $popoverContent
 */
define(
	[
		'jquery',
		'neos/content/ui/elements/toggle-button'
	],
	function ($, ToggleButton) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/elements/popover-button');

		return ToggleButton.extend({

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
					content: this.$popoverContent,
					preventLeft: (this.get('popoverPosition')==='left' ? false : true),
					preventRight: (this.get('popoverPosition')==='right' ? false : true),
					preventTop: (this.get('popoverPosition')==='top' ? false : true),
					preventBottom: (this.get('popoverPosition')==='bottom' ? false : true),
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
	}
);