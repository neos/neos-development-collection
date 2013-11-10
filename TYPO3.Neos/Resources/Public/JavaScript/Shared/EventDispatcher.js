/**
 * Event dispatcher
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies'
],
function(
	Ember,
	$
) {
	/**
	 * @singleton
	 */
	return Ember.Object.extend(Ember.Evented, {
		/**
		 * Trigger events for notification to external (site-specific) JavaScript.
		 *
		 * @param {string} eventName The name of the event to throw
		 * @param {string} message Message to add to the event
		 * @param {object} additionalData Optional object with additional attributes to include in the event
		 */
		triggerExternalEvent: function(eventName, message, additionalData) {
			var event,
				detail = {
					message: message,
					time: new Date()
				};
			if (additionalData && typeof additionalData === 'object') {
				$.extend(detail, additionalData);
			}
			if (typeof CustomEvent === 'function') {
				event = new CustomEvent(
					eventName,
					{
						detail: detail,
						bubbles: true,
						cancelable: true
					}
				);
			} else if (typeof document.createEvent === 'function') {
				event = document.createEvent('CustomEvent');
				event.initCustomEvent(eventName, true, true, detail);
			}
			document.dispatchEvent(event);
		}
	}).create();
});
