/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'text!phoenix/templates/content/ui/toolbar.html'
],
function(toolbarTemplate) {
	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};
	T3.Content.UI = T3.Content.UI || {};
	var $ = window.alohaQuery || window.jQuery;

	/**
	 * T3.Content.UI.Toolbar
	 *
	 * Toolbar which can contain other views. Has two areas, left and right.
	 */
	T3.Content.UI.Toolbar = SC.View.extend({
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
	T3.Content.UI.Button = SC.Button.extend({
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

	T3.Content.UI.Image = SC.View.extend({
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
	T3.Content.UI.ToggleButton = T3.Content.UI.Button.extend({
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
	T3.Content.UI.PopoverButton = T3.Content.UI.ToggleButton.extend({

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
});