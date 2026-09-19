/**
 * @typedef {Object} Notification
 * @property {string} severity
 * @property {string} title
 * @property {string} message
 * @property {number} code
 */

/**
 * @typedef {Object} EventDetails
 * @property {XMLHttpRequest} xhr
 */

/**
 * @typedef {Object} HtmxEvent
 * @property {EventDetails} detail
 */

document.addEventListener('DOMContentLoaded', () => {

	if (!window.htmx) {
		console.error('htmx is not loaded');
		return;
	}

	/**
	 * Show flash messages after successful requests.
	 * After notifying, ensure the notification container is shown in the browser top layer
	 * so it remains visible above any open popovers. This must happen in afterRequest
	 * (not afterSettle) because afterSettle fires before afterRequest — at settle time
	 * NeosCMS.Notification hasn't added its elements to the container yet.
	 */
	htmx.on('htmx:afterRequest', /** @param {HtmxEvent} e */(e) => {
		const flashMessagesJson = e.detail.xhr.getResponseHeader('X-Flow-FlashMessages');
		if (flashMessagesJson) {
			/** @type Notification[] */
			const flashMessages = JSON.parse(flashMessagesJson);
			flashMessages.forEach(({severity, title, message}) => {
				if (title) {
					NeosCMS.Notification[severity.toLowerCase()](title, message);
				} else {
					NeosCMS.Notification[severity.toLowerCase()](message);
				}
			});
		}

		const container = document.getElementById('neos-notification-container');
		if (container && container.children.length > 0) {
			try { container.showPopover(); } catch (e) { /* already open */ }
		}
	});

	/**
	 * Show error notifications for failed requests if no flash messages are present
	 */
	htmx.on('htmx:responseError', /** @param {HtmxEvent} e */(e) => {
		const flashMessagesJson = e.detail.xhr.getResponseHeader('X-Flow-FlashMessages');
		if (flashMessagesJson) {
			return;
		}

		const {status, statusText} = e.detail.xhr;
		NeosCMS.Notification.error(`Error ${status}: ${statusText}`);
	});
});
