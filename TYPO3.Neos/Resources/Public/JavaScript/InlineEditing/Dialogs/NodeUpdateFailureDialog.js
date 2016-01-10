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
			actions: {
				'reload': function() {
					location.reload();
				}
			},
			template: Ember.HTMLBars.compile(template)
		});
	}
);