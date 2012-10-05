require(
	requireConfiguration['TYPO3.TYPO3'],
	[
		'vie/instance'
	],
	function(vie) {

		buster.testCase('Test vie initialization', {
			'TYPO3 Namespaces are set': function() {
				'use strict';
				buster.assert.equals('http://www.typo3.org/ns/2011/FLOW3/Packages/TYPO3/Content/', vie.namespaces.get('typo3'));
			}
		});

	}
);