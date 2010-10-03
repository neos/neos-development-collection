Ext.ns("F3.TYPO3.UserInterface.Form");
/**
 * @class F3.TYPO3.UserInterface.Form.GenericForm
 * @namespace F3.TYPO3.UserInterface.Form
 * @extends Ext.form.FormPanel
 *
 * The generic form is a base for UI forms
 *
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

	onRenderLoad: function() {
		this.getForm().load({
			params: this.getLoadIdentifier(),
			success: this._loadValues,
			scope: this
		});
	},

	_loadValues: function(form, action) {
		var data = action.result.data,
			convertedData = this._convertNestedDataToFlatProperties(data);

		this.getForm().setValues(convertedData);
	},

	_convertNestedDataToFlatProperties: function(data) {
		var result = {};
		this._convertNestedDataToFlatPropertiesVisit(data, result, '')
		return result;
	},

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
	 * Submit the form
	 */
	doSubmitForm: function() {
		var data = this.getForm().getValues();
		data = this._convertFlatPropertiesToNestedData(data);
		Ext.apply(data, this.getSubmitIdentifier());
		this.getForm().api.submit.call(this, data, this.onSubmitSuccess, this);
	},

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
			this.el.mask('Loading...');
		}
		if (action.type === 'directsubmit') {
			this.el.mask('Saving...');
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
