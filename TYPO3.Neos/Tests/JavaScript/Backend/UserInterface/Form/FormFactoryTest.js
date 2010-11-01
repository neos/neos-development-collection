Ext.ns("F3.TYPO3.UserInterface.Form");

F3.TYPO3.UserInterface.Form.FormFactoryTest = {
	name: 'Test Registry',
	setUp: function() {
		this.factory = F3.TYPO3.UserInterface.Form.FormFactory;
		this.registry = F3.TYPO3.Core.Registry;
		this.registry._configuration = {
			schema: {
				"typo3:page": {
					service: {
						show: 'F3.TYPO3.UserInterface.Form',
						update: 'F3.TYPO3.UserInterface.Form'
					},
					properties: {
						'properties.title': {
							type: 'string',
							validations: [{
								key: 'v1',
								type: 'NotEmpty'
							}, {
								key: 'v2',
								type: 'Label'
							}, {
								key: 'v3',
								type: 'StringLength',
								options: {
									maximum: 50
								}
							}]
						},
						'properties.navigationTitle': {
							type: 'string'
						}
					}
				}
			},
			form: {
				editor: {
					// By type
					"string": {
						xtype: 'textfield'
					},
					"superStringEditor": {
						xtype: 'textarea',
						transform: function(a) { }
					}
				},
				type: {
					"typo3:page": {
						standard: {
							title: 'Page',
							children: [{
								key: 'pageProperties',
								type: 'fieldset',
								title: 'Page properties',
								children: [{
									key: 'title',
									type: 'field',
									property: 'properties.title',
									title: 'Page title'
								}, {
									key: 'navigationTitle',
									type: 'field',
									property: 'properties.navigationTitle',
									title: 'Navigation title'
								}]
							}]
						},
						pageProperties: {
							title: 'Page properties',
							children: [{
								key: 'title',
								type: 'field',
								property: 'properties.title',
								title: 'Page title'
							}, {
								key: 'navigationTitle',
								type: 'field',
								property: 'properties.navigationTitle',
								title: 'Navigation title'
							}]
						}
					}
				}
			}
		};
	},
	testCreateFormSetsType: function() {
		var config = this.factory.createForm('typo3:page');
		Y.Assert.areEqual('typo3:page', config.type);
	},
	testCreateFormWithoutViewUsesStandard: function() {
		var config = this.factory.createForm('typo3:page');
		Y.Assert.areEqual('Page', config.title);
	},
	testCreateFormWithViewUsesSpecifiedView: function() {
		var config = this.factory.createForm('typo3:page', 'pageProperties');
		Y.Assert.areEqual('Page properties', config.title);
	},
	testCreateFormAddsField: function() {
		var config = this.factory.createForm('typo3:page', 'pageProperties'),
			item;

		item = config.items[0];

		Y.Assert.areEqual('textfield', item.xtype);
		Y.Assert.areEqual('Page title', item.fieldLabel);
	},
	testCreateFormSetsApiFromSchema: function() {
		var config = this.factory.createForm('typo3:page', 'pageProperties');

		Y.Assert.areEqual(F3.TYPO3.UserInterface.Form, config.api.load);
		Y.Assert.areEqual(F3.TYPO3.UserInterface.Form, config.api.submit);
	},
	testCreateFormWithOverrideConfig: function() {
		var config = this.factory.createForm('typo3:page', 'pageProperties', {
			title: 'Foo'
		});

		Y.Assert.areEqual('Foo', config.title);
	}
};