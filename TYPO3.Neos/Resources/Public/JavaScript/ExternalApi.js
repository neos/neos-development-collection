define([
	'Content/ExternalApi',
	'Shared/Notification'
],
function(
	ContentApi,
	Notification
) {
	return {
		Content: ContentApi,
		Notification: Notification
	};
});