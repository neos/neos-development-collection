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
			template: Ember.required,
			classNames: ['neos-popover-toggle'],
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
					// Prevent popover to be immediately closed
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
