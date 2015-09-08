/**
 * Property Editor
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/Configuration',
	'Shared/Notification'
], function(
	Ember,
	$,
	Configuration,
	Notification
) {
	return Ember.ContainerView.extend({
		propertyDefinition: null,
		value: null,
		isModified: false,
		editorClassName: '',

		init: function() {
			this._super();
			this._loadView();
		},

		_loadView: function() {
			var that = this,
				propertyDefinition = this.get('propertyDefinition'),
				editor;

			//Ember.bind(this, 'value', 'inspector.nodeProperties.' + propertyDefinition.key);

			var editorOptions = $.extend(true,
				{
					elementId: propertyDefinition.elementId,
					property: propertyDefinition.key,
					propertyType: propertyDefinition.type,
					inspectorBinding: this.inspectorBinding,
					valueBinding: 'inspector.nodeProperties.' + propertyDefinition.key
				},
				Ember.get(propertyDefinition, 'ui.inspector.editorOptions') || {}
			);

			editor = Ember.get(propertyDefinition, 'ui.inspector.editor');

			if (!editor) {
				if (window.console && console.error) {
					console.error('Couldn\'t create editor for property "' + propertyDefinition.key + '" (no editor configured). Please check your NodeTypes.yaml configuration.');
				}
				Notification.error('Error loading inspector', 'Inspector property "' + propertyDefinition.key + '" could not be loaded because of a missing editor definition. See console for further details.');
				return;
			}

			if (editor.indexOf('Content/Inspector/Editors/') === 0) {
				// Rename old editor names for backwards compatibility
				editor = editor.replace('Content/Inspector/Editors/', 'TYPO3.Neos/Editors/');
			}
			if (editor.indexOf('TYPO3.Neos/Inspector/Editors/') === 0) {
				// Rename old editor names for backwards compatibility
				editor = editor.replace('TYPO3.Neos/Inspector/Editors/', 'TYPO3.Neos/Editors/');
			}
			// Convert last part of editor path into dashed class name
			var editorName = editor.substring(editor.lastIndexOf('/') + 1);
			this.set('editorClassName', editorName.replace(/([a-z\d])([A-Z])/g, '$1-$2').toLowerCase());

			require({context: 'neos'}, [editor], function(editorClass) {
				Ember.run(function() {
					if (!that.isDestroyed) {
						// It might happen that the editor was deselected before the require() call completed; so we
						// need to check again whether the view has been destroyed in the meantime.
						var editor = editorClass.create(editorOptions);
						that.set('currentView', editor);
					}
				});
			}, function() {
				if (window.console && window.console.error) {
					window.console.error('Couldn\'t create editor for property "' + propertyDefinition.key + '". The editor "' + editor + '" not found! Please check your configuration.');
				}
				Notification.error('Error loading inspector', 'Inspector editor for property "' + propertyDefinition.key + '" could not be loaded. See console for further details.');
			});
		},
	});
});