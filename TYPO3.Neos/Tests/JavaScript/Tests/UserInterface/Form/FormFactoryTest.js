Ext.ns("TYPO3.TYPO3.Components.Form");

describe("Test form factory", function() {

	var factory, registry;

	beforeEach(function() {
		factory = TYPO3.TYPO3.Components.Form.FormFactory;
		registry = TYPO3.TYPO3.Core.Registry;

		registry._configuration = {
			schema: {
				type: {
					"typo3:page": {
						service: {
							show: 'TYPO3.TYPO3.Components.Form',
							update: 'TYPO3.TYPO3.Components.Form'
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
		}
	});

	it ("Create form sets type.", function() {
		var config = factory.createForm('typo3:page');
		expect(config.type).toEqual('typo3:page');
	});

	it ("Create form without view uses standard.", function() {
		var config = factory.createForm('typo3:page');
		expect(config.title).toEqual('Page');
	});

	it ("Create form with view uses specified view.", function() {
		var config = factory.createForm('typo3:page', 'pageProperties');
		expect(config.title).toEqual('Page properties');
	});

	it ("Create form adds field.", function() {
		var config = factory.createForm('typo3:page', 'pageProperties');
		var item = config.items[0];

		expect(item.xtype).toEqual('textfield');
		expect(item.fieldLabel).toEqual('Page title');
	});

	it ("Create form sets API from schema.", function() {
		var config = factory.createForm('typo3:page', 'pageProperties');

		expect(config.api.load).toEqual(TYPO3.TYPO3.Components.Form);
		expect(config.api.submit).toEqual(TYPO3.TYPO3.Components.Form);
	});

	it ("Create form with override config.", function() {
		var config = factory.createForm('typo3:page', 'pageProperties', {
			title: 'Foo'
		});

		expect(config.title).toEqual('Foo', config.title);
	});

});