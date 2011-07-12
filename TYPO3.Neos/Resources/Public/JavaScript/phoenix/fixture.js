/**
 * Fixtures are things which lateron will come from the server side
 */

define([], function() {
	var fixture = {};

	fixture.availableModules = [
		{
			label: 'Users',
			url: '/users'
		},
		{
			label: 'Templates',
			url: '/templates'
		},
		{
			label: 'Configuration',
			url: '/configuration'
		},
		{
			label: 'Asset management',
			url: '/asset_management'
		}
	];

	return fixture;
});