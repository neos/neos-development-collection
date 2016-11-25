define([
	'Content/ExternalApi',
	'Shared/Notification',
	'Shared/I18n'
],
function(
	ContentApi,
	Notification,
	I18n
) {
	return {
		Content: ContentApi,
		Notification: Notification,
		I18n: I18n
	};
});