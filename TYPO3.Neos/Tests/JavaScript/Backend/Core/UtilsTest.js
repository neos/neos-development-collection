Ext.ns("F3.TYPO3.Core");

F3.TYPO3.Core.UtilsTest = {

	name: "Test Utils",

	setUp: function() {
		this.registry = F3.TYPO3.Core.Registry;
		this.registry.initialize();
	},

	testIsEmptyObjectWithEmptyObject: function() {
		Y.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject({}), true);
	},

	testIsEmptyObjectWithNonEmptyObject: function() {
		Y.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject({
			foo: 'bar'
		}), false);
	},

	testIsEmptyObjectWithString: function() {
		Y.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject('string'), false);
	},

	testIsEmptyObjectWithInteger: function() {
		Y.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject(0), false);
	},

	testIsEmptyObjectWithArray: function() {
		Y.Assert.areEqual(F3.TYPO3.Utils.isEmptyObject([]), false);
	}
};
