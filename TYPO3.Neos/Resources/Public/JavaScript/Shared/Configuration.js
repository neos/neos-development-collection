/**
 * An object containing general configuration
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies'
],
function(Ember, $) {

	/**
	 * @singleton
	 */
	return Ember.Object.extend({
		_data: {},

		init: function() {
			this.setProperties(window.T3Configuration);
			delete window.T3Configuration;
		},

		get: function(key, value) {
			if (arguments.length === 1) {
				if (this._data[key]) {
					return this._data[key];
				}

				switch(key) {
					case 'CsrfToken':
						this._data[key] = $('meta[name="neos-csrf-token"]').attr('content');
						return this._data[key];

					case 'NodeTypeSchemaUri':
						this._data[key] = $('link[rel="neos-nodetypeschema"]').attr('href');
						return this._data[key];

					case 'VieSchemaUri':
						this._data[key] = $('link[rel="neos-vieschema"]').attr('href');
						return this._data[key];

					case 'MenuDataUri':
						this._data[key] = $('link[rel="neos-menudata"]').attr('href');
						return this._data[key];

					case 'XliffUri':
						this._data[key] = $('link[rel="neos-xliff"]').attr('href');
						return this._data[key];

					case 'EditPreviewDataUri':
						this._data[key] = $('link[rel="neos-editpreviewdata"]').attr('href');
						return this._data[key];

					case 'ContentDimensionsUri':
						this._data[key] = $('link[rel="neos-service-contentdimensions"]').attr('href');
						return this._data[key];
				}
			}

			return this._super.apply(this, arguments);
		},

		override: function(key, value) {
			this.set('_data.' + key, value);
		}
	}).create();
});