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
	 */
	ll: {
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
					name: 'F3\FLOW3\Security\Authentication\Token\UsernamePassword::username',
					fieldLabel: this.ll.fieldUsername
				},
				{
					xtype: 'textfield',
					name: 'F3\FLOW3\Security\Authentication\Token\UsernamePassword::password',
					inputType: 'password',
					fieldLabel: this.ll.fieldPassword
				},
				{
					xtype: 'button',
					text: this.ll.buttonSubmit,
					handler: this.onSubmitButtonCLick,
					scope: this

				}
			]
		};
		Ext.apply(this, config);
		F3.TYPO3.Login.LoginForm.superclass.initComponent.call(this);
	},

	/**
	 * @method onSubmitButtonCLick
	 * @return void
	 */
	onSubmitButtonCLick: function() {
		this.getForm().submit({
			// TODO get the url from a F3.TYPO3.Config object
			url: '/typo3/login/authenticate.json',
			method: 'post'
		});
	}
});

Ext.reg('F3.TYPO3.Login.LoginForm', F3.TYPO3.Login.LoginForm);