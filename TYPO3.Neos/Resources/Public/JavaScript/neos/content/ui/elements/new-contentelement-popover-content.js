define(
	[
		'jquery',
		'emberjs',
		'text!neos/templates/content/ui/newContentelementPopoverContent.html'
	],
	function($, Ember, template) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/elements/new-contentelement-popover-content');

		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),

			_options: null,

			_index: null,

			didInsertElement: function() {
				var tabs = this.$('.contentTypeSelectorTabs').tabs();
				setTimeout(function() { tabs.tabs('select', 0);}, 100);
			},

			click: function(event) {
				if (!event.target.rel) {
					return;
				}
				this.get('_options').collection.add({
					'@type': event.target.rel
				}, {at: this.get('_index')});

				this.$().parents('.popover').trigger('hidePopover')
			}
		});
	}
)