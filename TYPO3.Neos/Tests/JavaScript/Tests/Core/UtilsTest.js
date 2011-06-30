Ext.ns('TYPO3.TYPO3.Core');

describe("Test Utils", function() {

	var registry;
	beforeEach(function() {
		registry = TYPO3.TYPO3.Core.Registry;
		registry.initialize();
	});

	it("Test isEmptyObject with empty object", function() {
		expect(TYPO3.TYPO3.Utils.isEmptyObject({})).toEqual(true);
	});

	it("Test isEmptyObject with non-empty object", function() {
		expect(TYPO3.TYPO3.Utils.isEmptyObject({
			foo: 'bar'
		})).toEqual(false);
	});

	it("Test isEmptyObject with string", function() {
		expect(TYPO3.TYPO3.Utils.isEmptyObject('string')).toEqual(false);
	});

	it("Test isEmptyObject with integer", function() {
		expect(TYPO3.TYPO3.Utils.isEmptyObject(0)).toEqual(false);
	});

	it("Test isEmptyObject with array", function() {
		expect(TYPO3.TYPO3.Utils.isEmptyObject([])).toEqual(false);
	});

	describe("getObjectByString", function() {

		beforeEach(function() {
			window.testObject = {
				b: {
					c: 'MyString',
					e: 'SomethingElse'
				},
				x: function() {
				}
			};
		});

		it("getObjectByString should return the correct object from global scope if it exists", function() {
			expect(TYPO3.TYPO3.Utils.getObjectByString('testObject.b')).toEqual(testObject.b);
			expect(TYPO3.TYPO3.Utils.getObjectByString('testObject.x')).toEqual(testObject.x);
		});

		it("getObjectByString should return undefined if the object does not exist", function() {
			expect(TYPO3.TYPO3.Utils.getObjectByString('testObject.b.c.d')).toBeUndefined();
			expect(TYPO3.TYPO3.Utils.getObjectByString('b')).toBeUndefined();
		});

		it("getObjectByString should return undefined if the passed parameter is not a string", function() {
			expect(TYPO3.TYPO3.Utils.getObjectByString(42)).toBeUndefined();
			expect(TYPO3.TYPO3.Utils.getObjectByString(undefined)).toBeUndefined();
			expect(TYPO3.TYPO3.Utils.getObjectByString({testObject: 'b'})).toBeUndefined();
			expect(TYPO3.TYPO3.Utils.getObjectByString(['asdf'])).toBeUndefined();
		});

		afterEach(function() {
			window.testObject = undefined;
		});
	});

});