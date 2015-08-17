define(
[
	'emberjs',
	'Content/Inspector/SecondaryInspectorController',
	'Library/codemirror'
],
function(Ember, SecondaryInspectorController, CodeMirror) {
	return SecondaryInspectorController.SecondaryInspectorButton.extend({
		buttonLabel: 'Edit code',
		labelBinding: 'buttonLabel',
		highlightingMode: 'text/html',

		viewClass: function() {
			var that = this;

			return Ember.View.extend({
				classNames: ['neos-secondary-inspector-code-editor'],
				template: Ember.Handlebars.compile('<textarea></textarea>'),

				didInsertElement: function() {
					var $editorContent = this.$().find('textarea'),
						value = that.get('value'),
						highlightingMode = that.get('highlightingMode');
					/*
					 * inserting the content into the textarea before creating the editor
					 * causes all contained inline-javascript to be executed
					 * we don't want that, additionally it breaks stuff (#33010)
					 *
					 * set the value of the editor instead
					 */
					var editor = CodeMirror.fromTextArea($editorContent.get(0), {
							mode: highlightingMode,
							theme: 'solarized dark',
							indentWithTabs: true,
							styleActiveLine: true,
							lineNumbers: true,
							lineWrapping: true
						});
					editor.on('change', function() {
						that.set('value', editor.getValue());
					});
					editor.setValue(value);
				}
			});
		}.property()
	});
});