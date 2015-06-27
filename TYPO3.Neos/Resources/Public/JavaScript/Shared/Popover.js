/**
* A Bootstrap Popover widget
*/
define([
		'emberjs',
		'Library/jquery-with-dependencies'
	], function(
		Ember,
		$
	) {
		return Ember.View.extend({
			defaultTemplate: Ember.Handlebars.compile('{{{view.anchorContent}}}'),
			classNames: ['neos-popover-toggle'],

			anchorContent: Ember.required,
			title: '',
			content: '',
			popoverContainer: false,
			placement: 'top',
			triggerMode: 'click',

			didInsertElement: function () {
				var that = this;
				that.$().popover({
					placement: that.placement,
					title: that.title,
					content: that.content,
					container: that.popoverContainer,
					trigger: that.triggerMode,
					html: true
				}).click(function(e) {
					// Prevent popover to be immediaely closed
					e.stopPropagation();
					e.preventDefault();
				});
			},
			willDestroyElement: function () {
				this.$().popover('destroy');
			}
		});
	}
);
