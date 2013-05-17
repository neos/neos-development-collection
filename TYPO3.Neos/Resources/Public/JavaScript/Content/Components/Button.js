/**
 * A simple, styled TYPO3 button.
 */
define(
	[
		'emberjs'
	],
	function (Ember) {
		return Ember.View.extend(Ember.TargetActionSupport, {
			tagName: 'button',
			classNames: ['t3-button','btn','btn-mini'],
			attributeBindings: ['disabled', 'title'],
			classNameBindings: ['iconClass', 'isActive'],
			disabled: false,
			visible: true,
			isActive: false,
			label: '',
			title: '',
			defaultTemplate: Ember.Handlebars.compile('{{view.label}}'),
			icon: '',
			iconClass: function() {
				var icon = this.get('icon');
				return icon !== '' ? 't3-icon-' + icon : '';
			}.property('icon'),

			mouseDown: function() {
				if (!this.get('disabled')) {
					this.set('isActive', true);
				}
			},
			mouseUp: function(event) {
				if (this.get('isActive')) {
					// Actually invoke the button's target and action.
					// This method comes from the Ember.TargetActionSupport mixin.
					this.triggerAction();
					this.set('isActive', false);
				}
			}
		});
	}
);