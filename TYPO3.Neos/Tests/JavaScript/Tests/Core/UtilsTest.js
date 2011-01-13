Ext.ns('F3.TYPO3.Core');

describe("Test Utils", function() {

	var registry;

	beforeEach(function() {
		registry = F3.TYPO3.Core.Registry;
		registry.initialize();
	});

	it("Test isEmptyObject with empty object", function() {
		expect(F3.TYPO3.Utils.isEmptyObject({})).toEqual(true);
	});

	it("Test isEmptyObject with non-empty object", function() {
		expect(F3.TYPO3.Utils.isEmptyObject({
			foo: 'bar'
		})).toEqual(false);
	});

	it("Test isEmptyObject with string", function() {
		expect(F3.TYPO3.Utils.isEmptyObject('string')).toEqual(false);
	});

	it("Test isEmptyObject with integer", function() {
		expect(F3.TYPO3.Utils.isEmptyObject(0)).toEqual(false);
	});

	it("Test isEmptyObject with array", function() {
		expect(F3.TYPO3.Utils.isEmptyObject([])).toEqual(false);
	});

	describe("getObjectByString", function() {

		beforeEach(function() {
			window.a = {
				b: {
					c: 'MyString',
					e: 'SomethingElse'
				},
				x: function() {
				}
			};
		});

		it("getObjectByString should return the correct object from global scope if it exists", function() {
			expect(F3.TYPO3.Utils.getObjectByString('a.b')).toEqual(window.a.b);
			expect(F3.TYPO3.Utils.getObjectByString('a.x')).toEqual(a.x);
		});

		it("getObjectByString should return undefined if the object does not exist", function() {
			expect(F3.TYPO3.Utils.getObjectByString('a.b.c.d')).toBeUndefined();
			expect(F3.TYPO3.Utils.getObjectByString('b')).toBeUndefined();
		});

		it("getObjectByString should return undefined if the passed parameter is not a string", function() {
			expect(F3.TYPO3.Utils.getObjectByString(42)).toBeUndefined();
			expect(F3.TYPO3.Utils.getObjectByString(undefined)).toBeUndefined();
			expect(F3.TYPO3.Utils.getObjectByString({a: 'b'})).toBeUndefined();
			expect(F3.TYPO3.Utils.getObjectByString(['asdf'])).toBeUndefined();
		});

		afterEach(function() {
			delete window.a;
		});
	});

});