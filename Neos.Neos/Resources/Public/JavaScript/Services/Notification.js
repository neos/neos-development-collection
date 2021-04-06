import Toast from "../Components/Notification/Toast";
import { isNil } from "../Helper";

const allowedTypes = ["ok", "info", "notice", "warning", "error"];

const _renderNotification = (title, message, type, additionalOptions) => {
	const options = { title: title, message: message, ...additionalOptions };
	if (allowedTypes.includes(type)) {
		options.type = type;
	}

	Toast.create(options);
};

/**
 * Show ok notification
 *
 * @param {string} title
 * @return {void}
 */
const ok = (title) => {
	_renderNotification(title, "", "ok");
};

/**
 * Show info notification
 *
 * @param {string} title
 * @return {void}
 */
const info = (title) => {
	_renderNotification(title, "", "info");
};

/**
 * Show notice notification
 *
 * @param {string} title
 * @return {void}
 */
const notice = (title) => {
	_renderNotification(title, "", "notice");
};

/**
 * Show warning notification
 *
 * @param {string} title
 * @param {string} message
 * @return {void}
 */
const warning = (title, message) => {
	_renderNotification(title, message, "warning", {
		timeout: 0,
		closeButton: true,
	});
};

/**
 * Show error notification
 *
 * @param {string} title
 * @param {string} message
 * @return {void}
 */
const error = (title, message) => {
	_renderNotification(title, message, "error", {
		timeout: 0,
		closeButton: true,
	});
};

/**
 * Clears all notifications
 *
 * @return {void}
 */
const clear = () => {
	Toast.removeAll();
};

const init = () => {
	if (isNil(window.NeosCMS)) {
		window.NeosCMS = {};
	}

	if (isNil(window.Typo3Neos)) {
		window.Typo3Neos = {};
	}

	if (isNil(window.NeosCMS.Notification)) {
		window.NeosCMS.Notification = {
			init: init,
			ok: ok,
			info: info,
			notice: notice,
			warning: warning,
			error: error,
			clear: clear,
		};

		// deprecated - to be removed in 8.0
		window.Typo3Neos.Notification = window.NeosCMS.Notification
	}

	const notifications = Array.from(
		document.querySelectorAll("#neos-notifications-inline li")
	);
	notifications.forEach((notificationElement) => {
		const type = notificationElement.getAttribute("data-type");
		const title = notificationElement.textContent;

		_renderNotification(title, "", type);
	});
};

export default { init, ok, info, notice, warning, error, clear };
