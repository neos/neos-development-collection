define([
	'emberjs',
	'Shared/HttpClient'
], function(
	Ember,
	HttpClient
) {
	return Ember.Object.create({
		updatePreference: function(preferenceKey, preferenceValue) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-user-preferences'),
				{data: {key: preferenceKey, value: preferenceValue}}
			);
		}
	});
});