// @ts-expect-error ts-migrate(7016) FIXME: Could not find a declaration file for module 'domp... Remove this comment to see the full error message
import DOMPurify from "dompurify";
import { isNil } from "../../Helper";
import { messageTemplate } from "./MessageTemplate";
import { MessageOptions } from "../../Interfaces/";

export default class Message {
	protected container?: HTMLElement;
	protected message?: HTMLElement;
	protected options: MessageOptions;

	constructor(_options: MessageOptions, _container: HTMLElement) {
		this.message = null;
		this.container = _container;
		this.options = _options;
		this.initialize();
		this.setupEventListeners();
	}

	private initialize(): void {
		const milliseconds = Date.now();
		const timestamp = Math.floor(milliseconds / 1000);
		const { title, message, type, closeButton } = this.options;
		const htmlSafeMessage = DOMPurify.sanitize(message);
		const messageMarkup = messageTemplate(
			type,
			title,
			htmlSafeMessage,
			closeButton
		);
		const messageElementWrapper = document.createElement("div");
		messageElementWrapper.innerHTML = messageMarkup;

		const messageElement = <HTMLElement>messageElementWrapper.firstElementChild;
		messageElement.id = "neos-notification-message-" + timestamp;
		this.message = messageElement;
		this.container.appendChild(messageElement);
		this.registerCloseButton(messageElement);
		this.registerExpandHandling(messageElement);
	}

	private registerExpandHandling(message: HTMLElement): void {
		const contentSection = message.querySelector(".neos-notification-content");
		if (
			!isNil(contentSection) &&
			contentSection.classList.contains("expandable")
		) {
			contentSection.addEventListener("click", this.toggle.bind(this));
		}
	}

	private registerCloseButton(message: HTMLElement): void {
		const closeButton = message.querySelector(".neos-close-button");
		if (!isNil(closeButton)) {
			closeButton.addEventListener("click", this.close.bind(this));
		}
	}

	private setupEventListeners(): void {
		const timeout: number = this.options.timeout;
		if (timeout > 0) {
			setTimeout(this.close.bind(this), timeout);
		}
	}

	private close(): void {
		if (!isNil(this.message)) {
			this.message.classList.add("fade-out");
			setTimeout(() => {
				this.message.remove();
			}, 250);
		}
	}

	private toggle(): void {
		if (isNil(this.message)) {
			return;
		}

		const contentSection: HTMLElement = this.message.querySelector(
			".neos-notification-content"
		);
		if (!isNil(contentSection)) {
			contentSection.classList.toggle("expanded");
		}
	}
}
