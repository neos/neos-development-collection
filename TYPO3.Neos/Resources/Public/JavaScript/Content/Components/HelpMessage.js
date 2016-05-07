/**
* Help message popover for providing contextual help
*/
define([
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/Popover'
	], function(
		Ember,
		$,
		Popover
	) {
		return Popover.extend({
			template: Ember.Handlebars.compile('<i {{bindAttr class="view.hovered:icon-question-circle-o:icon-question-circle :neos-help-message-icon"}}></i>'),
			tagName: 'a',
			classNames: ['neos-help-message-button'],
			placement: 'bottom',
			hovered: false,
			mouseEnter: function() {
				this.set('hovered', true);
			},
			mouseLeave: function() {
				this.set('hovered', false);
			}
		});
	}
);
