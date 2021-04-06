import { isEmpty, isNil } from "../../Helper";
import { IconTypesKeys, IconTypes } from "../../Interfaces/";

const iconTypes: IconTypes = {
	error: "error",
	info: "info",
	ok: "success",
	warning: "warning",
	notice: "info",
};

const messageTemplate = (
	type: IconTypesKeys,
	title: string,
	message?: string,
	close?: Boolean
) => {
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
