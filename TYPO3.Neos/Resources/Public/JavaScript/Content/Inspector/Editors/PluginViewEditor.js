define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Content/Inspector/Editors/SelectBoxEditor',
	'Content/Inspector/InspectorController',
	'Shared/HttpClient',
	'Shared/I18n'
],
function(
	Ember,
	$,
	SelectBoxEditor,
	InspectorController,
	HttpClient,
	I18n
) {
	return SelectBoxEditor.extend({
		allowEmpty: true,

		init: function() {
			this.set('placeholder', I18n.translate('TYPO3.Neos:Main:loading', 'Loading') + ' ...');
			this._loadOptionsOnChange();
			InspectorController.get('nodeProperties').addObserver('plugin', this, '_loadOptionsOnChange');

			this._super();
		},

		_loadOptionsOnChange: function() {
			var that = this,
				nodeIdentifier = InspectorController.get('nodeProperties.plugin');

			if (!Ember.empty(nodeIdentifier)) {
				HttpClient.getResource(
					$('link[rel="neos-pluginviews"]').attr('href'),
					{
						data: {
							identifier: nodeIdentifier,
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
						if (results[key] === undefined || results[key].label === undefined) {
							continue;
						}
						values[key] = {
							value: key,
							label: results[key].label
						};
						i++;
					}
					if (i > 0) {
						placeholder = I18n.translate('TYPO3.Neos:Main:content.inspector.editors.masterPluginEditor.selectPluginView', 'Select a plugin view');
					} else {
						placeholder = I18n.translate('TYPO3.Neos:Main:content.inspector.editors.masterPluginEditor.noPluginViewsConfigured', 'No plugin views configured');
						values = [];
					}
					that.setProperties({
						placeholder: placeholder,
						values: values
				   });
				});
			} else {
				that.setProperties({
					placeholder: 'No plugin selected',
					values: {},
					value: ''
				});
			}
		}
	});
});
