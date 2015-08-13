define(
[
	'Library/jquery-with-dependencies',
	'Content/Inspector/Editors/SelectBoxEditor'
],
function(
	$,
	SelectBoxEditor
) {
	return SelectBoxEditor.extend({
		init: function() {
			var that = this,
				url = $('link[rel="neos-masterplugins"]').attr('href');

			this.set('placeholder', 'Loading ...');
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
					placeholder = 'Select a Plugin';
				} else {
					placeholder = 'No plugin configured';
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