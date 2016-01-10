/**
 * A simple, styled Neos button.
 */
define(
	[
		'emberjs',
		'Shared/I18n'
	],
	function (Ember, I18n) {
		return Ember.View.extend(Ember.TargetActionSupport, {
			tagName: 'button',
			classNames: ['neos-button'],
			attributeBindings: ['disabled', 'title'],
			classNameBindings: ['iconClass', 'isActive'],
			disabled: false,
			visible: true,
			isActive: false,
			label: '',
			title: '',
			defaultTemplate: Ember.HTMLBars.compile('{{view.translatedLabel}}'),
			action: null,
			target: null,

			triggerAction: function() {
				var action = this.get('action');
				if (action) {
					var target = this.get('target');
					if (typeof target.send === 'function') {
						target.send(action);
					} else {
						target[action]();
					}
				}
				this._super();
			},

			translatedLabel: function() {
				return I18n.translate(this.get('label'));
			}.property('label'),

			icon: '',
			iconClass: function() {
				var icon = this.get('icon');
				return icon !== '' ? 'icon-' + icon : '';
			}.property('icon'),

			mouseDown: function() {
				if (!this.get('disabled')) {
					this.set('isActive', true);
					this._mouseDown = true;
					this._mouseEntered = true;
				}
				return this.get('propagateEvents');
			},

			mouseLeave: function() {
				if (this._mouseDown) {
					this.set('isActive', false);
					this._mouseEntered = false;
				}
			},

			mouseEnter: function() {
				if (this._mouseDown) {
					this.set('isActive', true);
					this._mouseEntered = true;
				}
			},

			mouseUp: function(event) {
				if (this.get('isActive')) {
					// Actually invoke the button's target and action.
					// This method comes from the Ember.TargetActionSupport mixin.
					this.triggerAction();
					this.set('isActive', false);
				}

				this._mouseDown = false;
				this._mouseEntered = false;
				return this.get('propagateEvents');
			},

			keyDown: function(event) {
				// Handle space or enter
				if (event.keyCode === 13 || event.keyCode === 32) {
					this.mouseDown();
				}
			},

			keyUp: function(event) {
				// Handle space or enter
				if (event.keyCode === 13 || event.keyCode === 32) {
					this.mouseUp();
				}
			},

			touchStart: function(touch) {
				return this.mouseDown(touch);
			},

			touchEnd: function(touch) {
				return this.mouseUp(touch);
			}
		});
	}
);
