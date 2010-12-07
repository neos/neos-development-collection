Ext.ns("F3.TYPO3.UserInterface.Form");

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @class F3.TYPO3.UserInterface.Form.FormFactory
 *
 * The form factory creates form component configurations from registry
 * information
 *
 * @namespace F3.TYPO3.UserInterface.Form
 * @extends Ext.util.Observable
 *
 * @singleton
 * @todo: why does the form factory extnd OBSERVABLE?
 */
F3.TYPO3.UserInterface.Form.FormFactory = new (Ext.extend(Ext.util.Observable, {
	/**
	 * Create a form for the given type and optional view or config
	 *
	 * @param {String} objectType the Object type a form should be created for. This is looked up in form/type/[....] in the registry.
	 * @param (String) view (optional) name of the view for the object. If none given, defaults to "standard"
	 * @param {Object} overrideConfig override configuration for the form
	 * @return {Object} resulting view configuration
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

	/**
	 * TODO: document!
	 *
	 * @param {Object} definition Form definition from the registry
	 * @param {String} objectType
	 * @param {String} defaultType
	 * @return {Object} resulting configuration
	 * @private
	 */
	_processDefinition: function(definition, objectType, defaultType) {
		var registry = F3.TYPO3.Core.Registry,
			type = definition.type,
			config, childDefaultType, fieldType, schemaDefinition, schemaPropertyDefinition;

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
					load: F3.TYPO3.Utils.getObjectByString(schemaDefinition.service.show),
					submit: F3.TYPO3.Utils.getObjectByString(schemaDefinition.service.update),
					update: F3.TYPO3.Utils.getObjectByString(schemaDefinition.service.update),
					create: F3.TYPO3.Utils.getObjectByString(schemaDefinition.service.create)
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

			if (schemaPropertyDefinition.validations) {
				Ext.each(schemaPropertyDefinition.validations, function(validation) {
					if (validation.type === 'NotEmpty') {
						config.allowBlank = false;
					} else if (validation.type === 'RegularExpression') {
						config.validator = function(value) {
							if (value.match(new RegExp(validation.options.regularExpression))) {
								return true;
							} else {
								return "The given subject did not match the pattern.";
							}
						};
					}
				});
			}
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