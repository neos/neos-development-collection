/**
 * Property Editor
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/Configuration'
], function(
	Ember,
	$,
	Configuration
) {
	return Ember.ContainerView.extend({
		propertyDefinition: null,
		value: null,
		isModified: false,
		hasValidationErrors: false,
		classNameBindings: ['isModified:neos-modified', 'hasValidationErrors:neos-error'],

		_valueDidChange: function() {
			if (this.get('inspector').isPropertyModified(this.get('propertyDefinition.key')) === true) {
				this.set('isModified', true);
			} else {
				this.set('isModified', false);
			}
		}.observes('value'),

		_validationErrorsDidChange: function() {
			if (this.get('isDestroyed') === true) {
				return;
			}
			var property = this.get('propertyDefinition.key'),
				validationErrors = this.get('inspector.validationErrors.' + property) || [];
			if (validationErrors.length > 0) {
				this.set('hasValidationErrors', true);
				this.$().tooltip('destroy').tooltip({
					animation: false,
					placement: 'bottom',
					title: validationErrors[0],
					trigger: 'manual'
				}).tooltip('show');
			} else {
				this.set('hasValidationErrors', false);
				this.$().tooltip('destroy');
			}
		},

		init: function() {
			this._super();
			this._loadView();
		},

		_loadView: function() {
			var that = this,
				propertyDefinition = this.get('propertyDefinition');
			Ember.bind(this, 'value', 'inspector.nodeProperties.' + propertyDefinition.key);

			var typeDefinition = Configuration.get('UserInterface.' + propertyDefinition.type);
			Ember.assert('Type defaults for "' + propertyDefinition.type + '" not found!', !!typeDefinition);

			var editorClassName = Ember.get(propertyDefinition, 'ui.inspector.editor') || typeDefinition.editor;
			Ember.assert('Editor class name for property "' + propertyDefinition.key + '" not found.', editorClassName);

			var editorOptions = $.extend(
				{
					elementId: propertyDefinition.elementId,
					inspectorBinding: this.inspectorBinding,
					valueBinding: 'inspector.nodeProperties.' + propertyDefinition.key
				},
				typeDefinition.editorOptions || {},
				Ember.get(propertyDefinition, 'ui.inspector.editorOptions') || {}
			);

			require([editorClassName], function(editorClass) {
				Ember.run(function() {
					if (!that.isDestroyed) {
						// It might happen that the editor was deselected before the require() call completed; so we
						// need to check again whether the view has been destroyed in the meantime.
						var editor = editorClass.create(editorOptions);
						that.set('currentView', editor);
					}
				});
			});
		},

		didInsertElement: function() {
			this.get('inspector.validationErrors').addObserver(this.get('propertyDefinition.key'), this, '_validationErrorsDidChange');
		}
	});
});