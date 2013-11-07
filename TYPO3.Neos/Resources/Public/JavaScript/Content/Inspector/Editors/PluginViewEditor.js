define(
[
	'Library/jquery-with-dependencies',
	'Content/Inspector/Editors/SelectBoxEditor',
	'Content/Inspector/InspectorController'
],
function(
	$,
	SelectBoxEditor,
	InspectorController
) {
	return SelectBoxEditor.extend({
		init: function() {
			this.set('placeholder', 'Loading ...');
			this._loadOptionsOnChange();
			InspectorController.get('nodeProperties').addObserver('plugin', this, '_loadOptionsOnChange')

			this._super();
		},

		_loadOptionsOnChange: function() {
			var that = this,
				nodePath = InspectorController.get('nodeProperties.plugin'),
				workspaceName = InspectorController.get('nodeProperties.__workspacename');

			if (!Ember.empty(nodePath)) {
				this._loadValuesFromController(
					$('link[rel="neos-pluginviews"]').attr('href') + '?node=' + nodePath + '@' + workspaceName,
					function(results) {
						var values = {}, placeholder, i = 0;

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
							placeholder = 'Select a View';
						} else {
							placeholder = 'No view configured for this plugin';
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
