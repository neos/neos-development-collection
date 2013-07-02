define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'Library/mousetrap'
],
function($, Ember, Mousetrap) {

	return Ember.Object.create({

		initializeContentModuleEvents: function() {
			Mousetrap.bind(['alt+p'], function () {
				T3.Content.Controller.Preview.togglePreview();
				return false;
			});
		}

	});
});