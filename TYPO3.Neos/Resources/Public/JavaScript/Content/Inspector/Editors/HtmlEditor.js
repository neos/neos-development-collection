define(
[
	'emberjs',
	'Content/Inspector/SecondaryInspectorController'
],
function(Ember, SecondaryInspectorController) {
	return SecondaryInspectorController.SecondaryInspectorButton.extend({

		label: 'Edit HTML',

		viewClass: function() {
			var that = this;

			return Ember.View.extend({
				classNames: ['neos-secondary-inspector-html-editor'],

				template: Ember.Handlebars.compile('<textarea></textarea>'),

				didInsertElement: function() {
					var $editorContent = this.$().find('textarea');
					$editorContent.html(that.get('value'));

					require([
						'Library/codemirror',
					], function(CodeMirror) {
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

						editorFullyPopulated = true;
					});
				}
			});
		}.property()
	});
});