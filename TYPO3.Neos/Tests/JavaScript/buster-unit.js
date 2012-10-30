var config = exports;

config['NeosDemoSiteDistribution'] = {
	rootPath: '../../../../../',
	environment: 'browser',
	extensions: [require('buster-amd')],
	autoRun: false,
	tests: [
		'Packages/Application/TYPO3.Neos/Tests/JavaScript/Unit/**/*.js'
	],
	libs: [
		'Packages/Application/TYPO3.Neos/Tests/JavaScript/require-configuration.js',
		'Packages/Application/TYPO3.Neos/Resources/Public/JavaScript/require.js'
	],
	resources: [
		'Web/_Resources/Static/Packages/TYPO3.Neos/**/*.js',
		'Packages/Application/TYPO3.Neos/Tests/JavaScript/Unit/**/*.js'
	],
	sources: []
};