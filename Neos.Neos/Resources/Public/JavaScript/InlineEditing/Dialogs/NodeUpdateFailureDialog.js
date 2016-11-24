define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Shared/AbstractModal',
		'text!./NodeUpdateFailureDialog.html'
	],
	function(
		$,
		Ember,
		AbstractModal,
		template
	) {
		return AbstractModal.extend({
			template: Ember.Handlebars.compile(template),

			'reload': function() {
				location.reload();
			}
		});
	}
);