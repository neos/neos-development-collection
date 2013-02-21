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

			_clickedButton: null,

			data: null,

			didInsertElement: function() {
				var tabs = this.$('.nodeTypeSelectorTabs').tabs();
				setTimeout(function() { tabs.tabs('select', 0); }, 100);
			},

			click: function(event) {
				var rel = $(event.target).closest('a').attr('rel');
				if (!rel) {
					return;
				}

				T3.Content.Controller.NodeActions.set('_elementIsAddingNewContent', this.getPath('_clickedButton._nodePath'));

				this.get('_options').collection.add({
					'@type': rel
				}, {at: this.get('_index')});

				this.$().parents('.popover').trigger('hidePopover');
			}
		});
	}
)