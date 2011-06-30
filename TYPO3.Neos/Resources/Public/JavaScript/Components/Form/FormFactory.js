Ext.ns('TYPO3.TYPO3.Components.Form');

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
 * @class TYPO3.TYPO3.Components.Form.FormFactory
 *
 * The form factory creates form component configurations from registry
 * information
 *
 * @namespace TYPO3.TYPO3.Components.Form
 * @extends Ext.util.Observable
 *
 * @singleton
 * @todo: why does the form factory extend OBSERVABLE?
 */
TYPO3.TYPO3.Components.Form.FormFactory = new (Ext.extend(Ext.util.Observable, {
	/**
	 * Create a form for the given type and optional view or config
	 *
	 * @param {String} objectType the Object type a form should be created for. This is looked up in form/type/[....] in the registry.
	 * @param {String} view (optional) name of the view for the object. If none given, defaults to "standard"
	 * @param {Object} overrideConfig override configuration for the form
	 * @return {Object} resulting view configuration
	 */
	createForm: function(objectType, view, overrideConfig) {
		var registry = TYPO3.TYPO3.Core.Registry,
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
		var registry = TYPO3.TYPO3.Core.Registry,
			type = definition.type,
			config, childDefaultType, fieldType, schemaDefinition, schemaPropertyDefinition;

		if (type === undefined) {
			type = defaultType;
		}

		if (type === 'custom') {
			return definition;
		} else if (type === 'form') {
			schemaDefinition = registry.get('schema/type/' + objectType);
			config = {
				xtype: 'TYPO3.TYPO3.Components.Form.GenericForm',
				type: objectType,
				title: definition.title,
				api: {
					// TODO Evaluate how to define APIs
					load: TYPO3.TYPO3.Utils.getObjectByString(schemaDefinition.service.show),
					submit: TYPO3.TYPO3.Utils.getObjectByString(schemaDefinition.service.update),
					update: TYPO3.TYPO3.Utils.getObjectByString(schemaDefinition.service.update),
					create: TYPO3.TYPO3.Utils.getObjectByString(schemaDefinition.service.create),
					move: TYPO3.TYPO3.Utils.getObjectByString(schemaDefinition.service.move)
				}
			};
			if (definition.layout !== undefined) {
				Ext.apply(config, {layout: definition.layout});
			}
			childDefaultType = 'field';
		} else if (type === 'field') {
			schemaPropertyDefinition = registry.get('schema/type/' + objectType + '/properties/' + definition.property);
			fieldType = schemaPropertyDefinition.type;
			config = TYPO3.TYPO3.Utils.clone(registry.get('form/editor/' + fieldType));

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
								return TYPO3.TYPO3.Core.I18n.get('TYPO3.TYPO3', 'valueDoesNotMatchPattern');
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
			config.items = this.getChildren(definition.children, objectType, childDefaultType);
		}

		return config;
	},

	getChildren: function(children, objectType, childDefaultType) {
		var items = [];
		Ext.each(children, function(child) {
			var config = this._processDefinition(child, objectType, childDefaultType);
			if (config === undefined) return;
			if (child.children !== undefined) {
				config.items = this.getChildren(child.children, objectType, childDefaultType);
			}
			items.push(config);
		}, this);
		return items;
	}
}));