var requireConfiguration = requireConfiguration || {};
requireConfiguration['TYPO3.TYPO3'] = {
	baseUrl: buster.env.contextPath + '/Web/_Resources/Static/Packages/',
	paths: {
		'vie/instance': 'TYPO3.TYPO3/JavaScript/vie/instance',
		'vie': 'TYPO3.TYPO3/Library/vie/vie-latest',
		'hallo': 'TYPO3.TYPO3/Library/hallo/hallo-min',
		'jquery': 'TYPO3.TYPO3/JavaScript/jquery',
		'jquery-ui': 'TYPO3.TYPO3/JavaScript/jquery-ui',
		'emberjs': 'TYPO3.TYPO3/Library/emberjs/ember-0.9.7.min',
		'Library/jquery/jquery-1.7.2': 'TYPO3.TYPO3/Library/jquery/jquery-1.7.2',
		'Library/jquery-ui/js/jquery-ui-1.9b1': 'TYPO3.TYPO3/Library/jquery-ui/js/jquery-ui-1.9b1',
		'backbone': 'TYPO3.TYPO3/Library/vie/lib/backboneJS/backbone.min',
		'underscorejs': 'TYPO3.TYPO3/Library/vie/lib/underscoreJS/underscore.min'
	},
	shim: {
		'emberjs': ['jquery'],
		'hallo': ['jquery', 'jquery-ui'],
		'jquery-ui': ['jquery'],
		'vie': ['backbone'],
		'backbone': ['underscorejs']
	}
};