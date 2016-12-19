define(
[
	'Library/jquery-with-dependencies',
	'Content/Inspector/Editors/SelectBoxEditor',
	'Shared/HttpClient',
	'Shared/I18n'
],
function(
	$,
	SelectBoxEditor,
	HttpClient,
	I18n
) {
	return SelectBoxEditor.extend({
		allowEmpty: true,

		init: function() {
			this._super();
			var that = this;

			this.set('placeholder', I18n.translate('Neos.Neos:Main:loading', 'Loading') + ' ...');
			HttpClient.getResource(
				$('link[rel="neos-masterplugins"]').attr('href'),
				{
					data: {
						workspaceName: $('#neos-document-metadata').data('neos-context-workspace-name'),
						dimensions: $('#neos-document-metadata').data('neos-context-dimensions')
					},
					dataType: 'json'
				}
			).then(function(results) {
				var values = {},
					placeholder,
					i = 0;

				for (var key in results) {
					if (results[key] === undefined) {
						continue;
					}
					values[key] = {
						value: key,
						label: results[key]
					};
					i++;
				}
				if (i > 0) {
					placeholder = I18n.translate('Neos.Neos:Main:content.inspector.editors.masterPluginEditor.selectPlugin', 'Select a Plugin');
				} else {
					placeholder = I18n.translate('Neos.Neos:Main:content.inspector.editors.masterPluginEditor.noPluginConfigured', 'No plugin configured');
					values = [];
				}

				that.setProperties({
					placeholder: placeholder,
					values: values
				});
			});
		}
	});
});
