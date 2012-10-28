require(
	requireConfiguration['TYPO3.TYPO3'],
	[
		'vie/instance'
	],
	function(vie) {

		buster.testCase('Test VIE initialization', {
			'TYPO3 Namespaces are set': function() {
				'use strict';
				buster.assert.equals('http://www.typo3.org/ns/2012/Flow/Packages/TYPO3/Content/', vie.namespaces.get('typo3'));
			}
		});

	}
);