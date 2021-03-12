import { isEmpty, isNil } from "../../Helper";

const iconTypes = {
	error: "error",
	info: "info",
	ok: "success",
	warning: "warning",
	notice: "info",
};

const messageTemplate = (title, message, type, close) => {
	const hasMessage = !isEmpty(message);
	const classNames = ["neos-notification-content"];
	const closeButton =
		!isNil(close) && close
			? '<i class="fas fa-times neos-close-button"></i>'
			: "";

	let messageText = "";
	if (hasMessage) {
		messageText = `<div class="neos-expand-content">${message}</div>`;
		classNames.push("expandable");
	}

	return `
		<div class="neos-notification neos-notification-${iconTypes[type]}">
			<i class="fas fa-${iconTypes[type]}"></i>
			${closeButton}
			<div class="${classNames.join(" ")}">
				<div class="neos-notification-heading">${title}</div>
				${messageText}
			</div>
		</div>
	`;
};

export { messageTemplate };
