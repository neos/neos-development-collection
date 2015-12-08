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
			tagName: 'a',
			classNames: ['neos-help-message-button'],
			anchorContent: '<i class="icon-question-sign neos-help-message-icon"></i>',
			placement: 'bottom'
		});
	}
);
