Ext.ns("F3.TYPO3.UserInterface.Form");
/**
 * @class F3.TYPO3.UserInterface.Form.FormFactory
 * @namespace F3.TYPO3.UserInterface.Form
 * @extends Ext.util.Observable
 *
 * The form factory creates form component configurations from registry
 * information
 *
 * @singleton
 */
F3.TYPO3.UserInterface.Form.FormFactory = new (Ext.extend(Ext.util.Observable, {
	/**
	 * Create a form for the given type and optional view or config
	 */
	createForm: function(objectType, view, overrideConfig) {
		var registry = F3.TYPO3.Core.Registry,
			definition,
			config;

		if (view === undefined) {
			view = 'standard';
		}

		definition = registry.get('form/type/' + objectType + '/' + view);

		config = this._processDefinition(definition, objectType, 'form');

		Ext.apply(config, overrideConfig);

		return config;
	},

	_processDefinition: function(definition, objectType, defaultType) {
		var registry = F3.TYPO3.Core.Registry,
			type = definition.type,
			config,	childDefaultType, fieldType, schemaDefinition, schemaPropertyDefinition;

		if (type === undefined) {
			type = defaultType;
		}

		if (type == 'form') {
			schemaDefinition = registry.get('schema/type/' + objectType);
			config = {
				xtype: 'F3.TYPO3.UserInterface.Form.GenericForm',
				type: objectType,
				title: definition.title,
				api: {
					// TODO Evaluate how to define APIs
					load: eval(schemaDefinition.service.show),
					submit: eval(schemaDefinition.service.update),
					update: eval(schemaDefinition.service.update),
					create: eval(schemaDefinition.service.create)
				}
			}
			childDefaultType = 'field';
		} else if (type == 'field') {
			schemaPropertyDefinition = registry.get('schema/type/' + objectType + '/properties/' + definition.property);
			fieldType = schemaPropertyDefinition.type;
			config = F3.TYPO3.Utils.clone(registry.get('form/editor/' + fieldType));

			Ext.apply(config, {
				name: definition.property,
				fieldLabel: definition.title
			});
		} else {
			// Nothing found!
			return undefined;
		}

		if (definition.children !== undefined) {
			config.items = [];
			Ext.each(definition.children, function(child) {
				config.items.push(this._processDefinition(child, objectType, childDefaultType));
			}, this);
		}

		return config;
	}
}));
