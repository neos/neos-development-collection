define(
[
	'Library/jquery-with-dependencies',
	'Content/Inspector/Editors/SelectBoxEditor',
	'Shared/I18n'
],
function(
	$,
	SelectBoxEditor,
	I18n
) {
	return SelectBoxEditor.extend({
		init: function() {
			var that = this,
				url = $('link[rel="neos-masterplugins"]').attr('href');

			this.set('placeholder', I18n.translate('Main:TYPO3.Neos:loading', 'Loading ...'));
			this._loadValuesFromController(url, function(results) {
				var values = {}, placeholder, i = 0;
				values[''] = {};

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
					placeholder = I18n.translate('Main:TYPO3.Neos:content.inspector.editors.masterPluginEditor.selectPlugin', 'Select a Plugin');
				} else {
					placeholder = I18n.translate('Main:TYPO3.Neos:content.inspector.editors.masterPluginEditor.noPluginConfigured', 'No plugin configured');
					values = {};
				}
				that.setProperties({
					placeholder: placeholder,
					values: values
				});
			});

			this._super();
		}
	});
});