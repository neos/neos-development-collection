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
    this._initialize();
    this._setupEventListeners();
  }

  _initialize() {
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
    this._registerCloseButton(messageElement);
    this._registerExpandHandling(messageElement);
  }

  _registerExpandHandling(message: HTMLElement) {
    const contentSection = message.querySelector(".neos-notification-content");
    if (
      !isNil(contentSection) &&
      contentSection.classList.contains("expandable")
    ) {
      contentSection.addEventListener("click", this._toggle.bind(this));
    }
  }

  _registerCloseButton(message: HTMLElement) {
    const closeButton = message.querySelector(".neos-close-button");
    if (!isNil(closeButton)) {
      closeButton.addEventListener("click", this._close.bind(this));
    }
  }

  _setupEventListeners() {
    const timeout: number = this.options.timeout;
    if (timeout > 0) {
      setTimeout(this._close.bind(this), timeout);
    }
  }

  _close() {
    if (!isNil(this.message)) {
      this.message.classList.add("fade-out");
      setTimeout(() => {
        this.message.remove();
      }, 250);
    }
  }

  _toggle() {
    if (isNil(this.message)) {
      return false;
    }

    const contentSection: HTMLElement = this.message.querySelector(
      ".neos-notification-content"
    );
    if (!isNil(contentSection)) {
      contentSection.classList.toggle("expanded");
    }
  }
}
