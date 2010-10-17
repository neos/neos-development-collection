Ext.ns("F3.TYPO3.Core");

F3.TYPO3.Core.UtilsTest = new YAHOO.tool.TestCase({

	name: "Test Utils",

	setUp: function() {
		this.registry = F3.TYPO3.Core.Registry;
		this.registry.initialize();
	},

	testIsEmptyObjectWithEmptyObject: function() {
		YAHOO.util.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject({}), true);
	},

	testIsEmptyObjectWithNonEmptyObject: function() {
		YAHOO.util.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject({
			foo: 'bar'
		}), false);
	},

	testIsEmptyObjectWithString: function() {
		YAHOO.util.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject('string'), false);
	},

	testIsEmptyObjectWithInteger: function() {
		YAHOO.util.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject(0), false);
	},

	testIsEmptyObjectWithArray: function() {
		YAHOO.util.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject([]), false);
	}
});