/**
 * A simple, styled TYPO3 button.
 */
define(
	[
		'emberjs'
	],
	function (Ember) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('phoenix/content/ui/elements/button');

		return Ember.Button.extend({
			classNames: ['t3-button','btn','btn-mini'],
			attributeBindings: ['disabled'],
			classNameBindings: ['iconClass'],
			label: '',
			disabled: false,
			visible: true,
			icon: '',
			template: Ember.Handlebars.compile('{{label}}'),
			iconClass: function() {
				var icon = this.get('icon');
				return icon !== '' ? 't3-icon-' + icon : '';
			}.property('icon').cacheable()
		});
	}
);