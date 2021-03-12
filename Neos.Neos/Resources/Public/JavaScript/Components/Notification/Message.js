import DOMPurify from "dompurify";
import { isNil } from "../../Helper";
import { messageTemplate } from "./MessageTemplate";

export default class Message {
	constructor(_options, _container) {
		this._message = null;
		this._container = _container;
		this._options = _options;
		this._initialize();
		this._setupEventListeners();
	}

	_initialize() {
		const milliseconds = Date.now();
		const timestamp = Math.floor(milliseconds / 1000);
		const { title, message, type, closeButton } = this._options;
		const htmlSafeMessage = DOMPurify.sanitize(message);
		const messageMarkup = messageTemplate(
			title,
			htmlSafeMessage,
			type,
			closeButton
		);
		const messageElementWrapper = document.createElement("div");
		messageElementWrapper.innerHTML = messageMarkup;

		const messageElement = messageElementWrapper.firstElementChild;
		messageElement.id = "neos-notification-message-" + timestamp;
		this._message = messageElement;
		this._container.appendChild(messageElement);
		this._registerCloseButton(messageElement);
		this._registerExpandHandling(messageElement);
	}

	_registerExpandHandling(message) {
		const contentSection = message.querySelector(".neos-notification-content");
		if (
			!isNil(contentSection) &&
			contentSection.classList.contains("expandable")
		) {
			contentSection.addEventListener("click", this._toggle.bind(this));
		}
	}

	_registerCloseButton(message) {
		const closeButton = message.querySelector(".neos-close-button");
		if (!isNil(closeButton)) {
			closeButton.addEventListener("click", this._close.bind(this));
		}
	}

	_setupEventListeners() {
		const timeout = parseInt(this._options.timeout);
		if (!isNaN(timeout) && timeout > 0) {
			setTimeout(this._close.bind(this), timeout);
		}
	}

	_close() {
		if (!isNil(this._message)) {
			this._message.classList.add("fade-out");
			setTimeout(() => {this._message.remove()}, 250);
		}
	}

	_toggle() {
		if (isNil(this._message)) {
			return false;
		}

		const contentSection = this._message.querySelector(
			".neos-notification-content"
		);
		if (!isNil(contentSection)) {
			contentSection.classList.toggle("expanded");
		}
	}
}
