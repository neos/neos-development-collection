define(
[
	'./Application',
	'./ApplicationView',

	'neos/common',
	'neos/content/model',
	'neos/content/ui',
	'neos/content/controller',
	'create/typo3Notifications'

],
function(Application, ApplicationView) {
	ApplicationView.create().appendTo('#t3-application');
	return Application;
});