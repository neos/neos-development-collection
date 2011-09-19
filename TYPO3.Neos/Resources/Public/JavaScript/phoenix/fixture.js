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

	// TODO: think about the name
	fixture.typeDefaults = {
		'string': {
			editor: {
				'class': 'T3.Content.UI.Editor.TextField',
				defaults: {
					size: 50
				}
			},
			renderer: {
				'class': 'T3.Content.UI.Renderer.Text',
				defaults: {
					crop: 50
				}
			}
		},
		'boolean': {
			editor: {
				'class': 'T3.Content.UI.Editor.Checkbox'
			},
			renderer: {
				'class': 'T3.Content.UI.Renderer.Boolean'
			}
		},
		'image': {
			editor: {
				'class': 'T3.Content.UI.Editor.FileUpload',
				defaults: {
					allowedFileTypes: ['jpg', 'png']
				}
			},
			renderer: {
				'class': 'T3.Content.UI.Renderer.File'
			}
		}
	};

	return fixture;
});