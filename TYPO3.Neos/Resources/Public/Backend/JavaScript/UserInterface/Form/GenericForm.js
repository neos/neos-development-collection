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
 * @class F3.TYPO3.UserInterface.Form.GenericForm
 *
 * The generic form is a base for UI forms
 *
 * @namespace F3.TYPO3.UserInterface.Form
 * @extends Ext.form.FormPanel
 */
F3.TYPO3.UserInterface.Form.GenericForm = Ext.extend(Ext.form.FormPanel, {
	initComponent: function() {
		var config = {
			paramsAsHash: true,
			defaults: {
				 listeners: {
					specialkey: {
						fn: function(field, e){
							if (e.getKey() == e.ENTER) {
								this.doSubmitForm();
							}
						},
						scope: this
					}
				 }
			},

			border: false,
			layoutConfig: {
				labelSeparator: ''
			},
			header: false,
			cls: 'F3-TYPO3-UserInterface-Form-GenericForm'
		};
		Ext.apply(this, Ext.apply(this.initialConfig, config));
		F3.TYPO3.UserInterface.Form.GenericForm.superclass.initComponent.call(this);

			// add event listener
		this.on('beforeaction', this._onFormBeforeAction , this);
		this.on('actioncomplete', this._onFormActionComplete, this);
		this.on('actionfailed', this._onFormActionComplete, this);

		this.on('render', this.onRenderLoad, this);
	},

	/**
	 * Load initial values for the form on render event.
	 *
	 * Override this method to implement custom form load logic.
	 * To not do any loading, specify autoLoad: false for the form.
	 *
	 * @return {void}
	 */
	onRenderLoad: function() {
		if (this.autoLoad !== false) {
			this.getForm().load({
				params: this.getLoadIdentifier(),
				success: this._loadValues,
				scope: this
			});
		}
	},

	/**
	 * Fill the form with the loaded data
	 *
	 * @param {...} form
	 * @param {...} action
	 * @return {void}
	 * @private
	 */
	_loadValues: function(form, action) {
		var data, convertedData;

		data = action.result.data;
		convertedData = this._convertNestedDataToFlatProperties(data);

		this.getForm().setValues(convertedData);
	},

	/**
	 * TODO: Document
	 *
	 * @param {...}
	 * @return {...}
	 * @private
	 */
	_convertNestedDataToFlatProperties: function(data) {
		var result = {};
		this._convertNestedDataToFlatPropertiesVisit(data, result, '')
		return result;
	},

	/**
	 * TODO: Document
	 *
	 * @param {...}
	 * @param {...}
	 * @param {...}
	 * @return {void}
	 * @private
	 */
	_convertNestedDataToFlatPropertiesVisit: function(data, result, path) {
		var key;
		for (key in data) {
			if (Ext.isObject(data[key])) {
				this._convertNestedDataToFlatPropertiesVisit(data[key], result, (path !== '' ? path + '.' + key : key));
			} else {
				result[(path !== '' ? path + '.' + key : key)] = data[key];
			}
		}
	},

	/**
	 * Validate form and submit
	 *
	 * @return {void}
	 */
	doSubmitForm: function() {
		if (!this.getForm().isValid()) return;
		var data = this.getForm().getValues();
		data = this._convertFlatPropertiesToNestedData(data);
		data['__contextNodePath'] = this.getSubmitIdentifier();
		this.getForm().api.submit.call(this, data, this.onSubmitSuccess, this);
	},

	/**
	 * TODO: document
	 *
	 * @param {Object} properties
	 * @return {...}
	 * @private
	 */
	_convertFlatPropertiesToNestedData: function(properties) {
		var result = {}, key, context;
		for (key in properties) {
			context = result;
			Ext.each(key.split('.').slice(0, -1), function(keyPart) {
				if (context[keyPart] === undefined) {
					context[keyPart] = {};
				}
				context = context[keyPart];
			});
			// TODO JS the bad part
			context[key.split('.').slice(-1)[0]] = properties[key];
		}
		return result;
	},

	/**
	 * Provide a custom function for getting the identifier
	 */
	getLoadIdentifier: function() {
	},

	/**
	 * Provide a custom function for getting the identifier
	 */
	getSubmitIdentifier: function() {
	},

	/**
	 * Fired if the submit action succeeded
	 */
	onSubmitSuccess: function() {
	},

	/**
	 * on form before action, triggered before any action is performed.
	 *
	 * @param {} form
	 * @param {} action
	 * ®return {void}
	 */
	_onFormBeforeAction: function(form, action) {
		if (action.type === 'directload') {
			this.el.mask(F3.TYPO3.UserInterface.I18n.get('TYPO3', 'currentlyLoading'));
		}
		if (action.type === 'directsubmit') {
			this.el.mask(F3.TYPO3.UserInterface.I18n.get('TYPO3', 'currentlySaving'));
		}
	},

	/**
	 * on form action complete
	 *
	 * @param {} form
	 * @param {} action
	 * ®return {void}
	 */
	_onFormActionComplete: function(form, action) {
		if (action.type === 'directload') {
			this.el.unmask();
		}
		if (action.type === 'directsubmit') {
			this.el.unmask();
		}
	}
});
Ext.reg('F3.TYPO3.UserInterface.Form.GenericForm', F3.TYPO3.UserInterface.Form.GenericForm);