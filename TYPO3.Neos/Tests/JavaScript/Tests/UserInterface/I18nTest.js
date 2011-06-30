Ext.ns('TYPO3.TYPO3.UserInterface');

describe("Test translation object", function() {

	var proxy;

	beforeEach(function() {
		proxy = TYPO3.TYPO3.Core.I18n;
		proxy._initialized = true;
	});

	it ('Test basic fetching of localizations from the internal data object', function() {
		proxy._data = {
			foo: {
				bar: 'baz',
				bar_2: 'baz 2'
			},
			Bar: {
				foo: 'this is a foo'
			}
		};

		expect(proxy.get('foo', 'bar')).toEqual('baz');
		expect(proxy.get('foo', 'bar_2')).toEqual('baz 2');
		expect(proxy.get('Bar', 'foo')).toEqual('this is a foo');
	});

});