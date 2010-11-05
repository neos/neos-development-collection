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
	},

	getObjectByStringSetUp: function() {
		window.a = {
			b: {
				c: 'MyString',
				e: 'SomethingElse'
			},
			x: function() {
			}
		};
	},
	getObjectByStringTearDown: function() {
		delete window.a;
	},
	'getObjectByString should return the correct object from global scope if it exists': function() {
		this.getObjectByStringSetUp();

		Y.Assert.areEqual(
			window.a.b,
			F3.TYPO3.Utils.getObjectByString('a.b')
		);

		Y.Assert.areEqual(
			a.x,
			F3.TYPO3.Utils.getObjectByString('a.x')
		);

		this.getObjectByStringTearDown();
	},

	'getObjectByString should return undefined if the object does not exist': function() {
		this.getObjectByStringSetUp();

		Y.Assert.areEqual(
			undefined,
			F3.TYPO3.Utils.getObjectByString('a.b.c.d')
		);

		Y.Assert.areEqual(
			undefined,
			F3.TYPO3.Utils.getObjectByString('b')
		);

		this.getObjectByStringTearDown();
	},
	'getObjectByString should return undefined if the passed parameter is not a string': function() {
		Y.Assert.areEqual(
			undefined,
			F3.TYPO3.Utils.getObjectByString(42)
		);
		Y.Assert.areEqual(
			undefined,
			F3.TYPO3.Utils.getObjectByString(undefined)
		);

		Y.Assert.areEqual(
			undefined,
			F3.TYPO3.Utils.getObjectByString({a: 'b'})
		);

		Y.Assert.areEqual(
			undefined,
			F3.TYPO3.Utils.getObjectByString(['asdf'])
		);
	}
};