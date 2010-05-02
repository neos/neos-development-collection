Ext.ns('F3.TYPO3.Login');

/**
 * @class F3.TYPO3.Login.LoginForm
 * @namespace F3.TYPO3.Login
 * @extends Ext.form.FormPanel
 *
 * Login form and login handling, redirect if login was successfull
 */
F3.TYPO3.Login.LoginForm = Ext.extend(Ext.form.FormPanel, {

	/**
	 * Language Labels
	 * @private
	 */
	ll: { // TODO: Localization
		fieldUsername: 'Username',
		fieldPassword: 'Password',
		buttonSubmit: 'Submit'
	},

	/**
	 * @method initComponent
	 * @return void
	 */
	initComponent: function() {
		var config = {
			border: false,
			height: 120,
			items: [
				{
					xtype: 'textfield',
					name: 'F3\\FLOW3\\Security\\Authentication\\Token\\UsernamePassword\:\:username',
					fieldLabel: this.ll.fieldUsername
				},
				{
					xtype: 'textfield',
					name: 'F3\\FLOW3\\Security\\Authentication\\Token\\UsernamePassword\:\:password',
					inputType: 'password',
					fieldLabel: this.ll.fieldPassword
				},
				{
					xtype: 'button',
					type: 'submit',
					text: this.ll.buttonSubmit,
					handler: this.onSubmitButtonClick,
					scope: this

				}
			]
		};
		Ext.apply(this, config);
		F3.TYPO3.Login.LoginForm.superclass.initComponent.call(this);
	},

	/**
	 * @method onSubmitButtonClick
	 * @return void
	 * @private
	 */
	onSubmitButtonClick: function() {
		this.getForm().submit({
			url: F3.TYPO3.Utils.buildBackendUri('login/authenticate.json'),
			success: this.onLoginSuccess,
			scope: this
		});
	},

	/**
	 * On login success redirect to the backend
	 * @method onLoginSuccess
	 * @param object form
	 * @param object action
	 * @return void
	 * @private
	 */
	onLoginSuccess: function(form, action) {
		location.href = action.result.redirectUri;
	}
});

Ext.reg('F3.TYPO3.Login.LoginForm', F3.TYPO3.Login.LoginForm);