define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Content/Inspector/SelectBoxEditor',
	'Content/Inspector/InspectorController',
	'Shared/I18n'
],
function(
	Ember,
	$,
	SelectBoxEditor,
	InspectorController,
	I18n
) {
	return SelectBoxEditor.extend({
		init: function() {
			this.set('placeholder', I18n.translate('TYPO3.Neos:Main:loading', 'Loading ...'));
			this._loadOptionsOnChange();
			InspectorController.get('nodeProperties').addObserver('plugin', this, '_loadOptionsOnChange');

			this._super();
		},

		_loadOptionsOnChange: function() {
			var that = this,
				nodePath = InspectorController.get('nodeProperties.plugin'),
				workspaceName = $('#neos-document-metadata').data('neos-context-workspace-name'),
				dimensions = $('#neos-document-metadata').data('neos-context-dimensions'),
				dimensionValues = Object.keys(dimensions).reduce(function (previous, key) {
					return previous + (previous ? '&' : '') + key + '=' + dimensions[key].join(',');
				}, '');

			if (!Ember.empty(nodePath)) {
				this._loadValuesFromController(
					$('link[rel="neos-pluginviews"]').attr('href'),
					[{name: 'node', value: nodePath + '@' + workspaceName + (dimensionValues !== '' ? ';' + dimensionValues : '')}],
					function(results) {
						var values = {},
							placeholder,
							i = 0;

						values[''] = {};

						for (var key in results) {
							if (results[key] === undefined || results[key].label === undefined) {
								continue;
							}
							values[key] = {
								value: key,
								label: results[key].label,
								disabled: results[key].pageNode !== undefined
							};
							i++;
						}
						if (i > 0) {
							placeholder = I18n.translate('TYPO3.Neos:Main:content.inspector.editors.masterPluginEditor.selectPlugin', 'Select a Plugin');
						} else {
							placeholder = I18n.translate('TYPO3.Neos:Main:content.inspector.editors.masterPluginEditor.noPluginConfigured', 'No plugin configured');
							values = {};
						}
						that.setProperties({
							placeholder: placeholder,
							values: values
					   });
					}
				);
			} else {
				this.set('placeholder', 'No Plugin selected');
			}
		}
	});
});
