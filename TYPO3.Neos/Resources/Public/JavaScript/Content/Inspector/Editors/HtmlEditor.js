define(
[
	'emberjs',
	'Content/Inspector/SecondaryInspectorController',
	'Library/codemirror'
],
function(Ember, SecondaryInspectorController, CodeMirror) {
	return SecondaryInspectorController.SecondaryInspectorButton.extend({

		label: 'Edit HTML',

		viewClass: function() {
			var that = this;

			return Ember.View.extend({
				classNames: ['neos-secondary-inspector-html-editor'],
				template: Ember.Handlebars.compile('<textarea></textarea>'),

				didInsertElement: function() {
					var $editorContent = this.$().find('textarea');
					var value = that.get('value');
					/*
					 * inserting the content into the textarea before creating the editor
					 * causes all contained inline-javascript to be executed
					 * we don't want that, additionally it breaks stuff (#33010)
					 *
					 * set the value of the editor instead
					 */

					var editorFullyPopulated = false;
					var editor = CodeMirror.fromTextArea($editorContent.get(0), {
						mode: 'text/html',
						tabMode: 'indent',
						theme: 'solarized dark',
						lineNumbers: true,
						onChange: function() {
							if (editor && editorFullyPopulated) {
								that.set('value', editor.getValue());
							}
						}
					});
					editor.setValue(value);
					editorFullyPopulated = true;
				}
			});
		}.property()
	});
});