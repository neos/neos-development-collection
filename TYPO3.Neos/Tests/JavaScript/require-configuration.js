var requireConfiguration = requireConfiguration || {};
requireConfiguration['TYPO3.Neos'] = {
	baseUrl: buster.env.contextPath + '/Web/_Resources/Static/Packages/',
	paths: {
		'vie/instance': 'TYPO3.Neos/JavaScript/vie/instance',
		'vie': 'TYPO3.Neos/Library/vie/vie-latest',
		'hallo': 'TYPO3.Neos/Library/hallo/hallo-min',
		'jquery': 'TYPO3.Neos/JavaScript/jquery',
		'jquery-ui': 'TYPO3.Neos/JavaScript/jquery-ui',
		'emberjs': 'TYPO3.Neos/Library/emberjs/ember-0.9.7.min',
		'Library/jquery/jquery-1.7.2': 'TYPO3.Neos/Library/jquery/jquery-1.7.2',
		'Library/jquery-ui/js/jquery-ui-1.9b1': 'TYPO3.Neos/Library/jquery-ui/js/jquery-ui-1.9b1',
		'backbone': 'TYPO3.Neos/Library/vie/lib/backboneJS/backbone.min',
		'underscorejs': 'TYPO3.Neos/Library/vie/lib/underscoreJS/underscore.min'
	},
	shim: {
		'emberjs': ['jquery'],
		'hallo': ['jquery', 'jquery-ui'],
		'jquery-ui': ['jquery'],
		'vie': ['backbone'],
		'backbone': ['underscorejs']
	}
};