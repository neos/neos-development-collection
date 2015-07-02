require(
	requireConfiguration['TYPO3.Neos'],
	[
		'vie/instance'
	],
	function(vie) {

		buster.testCase('Test VIE initialization', {
			'TYPO3 Namespaces are set': function() {
				'use strict';
				buster.assert.equals('http://www.typo3.org/ns/2012/Flow/Packages/Neos/Content/', vie.namespaces.get('typo3'));
			}
		});

	}
);