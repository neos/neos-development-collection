define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'LibraryExtensions/Mousetrap',
	'InlineEditing/ContentCommands'
],
function($, Ember, Mousetrap, ContentCommands) {
	return Ember.Object.create({
		initializeContentModuleEvents: function() {
			Mousetrap.bind(['alt+p'], function () {
				T3.Content.Controller.Preview.togglePreview();
				return false;
			});

			Mousetrap.bind('ctrl+alt+a', function() {
				ContentCommands.create();
			});

			Mousetrap.bind('ctrl+alt+v', function() {
				ContentCommands.paste();
			});

			Mousetrap.bind('ctrl+alt+c', function() {
				ContentCommands.copy();
			});

			Mousetrap.bind('ctrl+alt+x', function() {
				ContentCommands.cut();
			});

			Mousetrap.bind('ctrl+alt+d', function() {
				ContentCommands.remove();
			});

			Mousetrap.bind('ctrl+alt+del', function() {
				window.location.reload();
			});
		}
	});
});
