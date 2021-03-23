import { isNil } from "../../Helper";
import Message from "./Message";
import { ToastDefaultOptions, MessageOptions } from "../../Interfaces/";

const defaultOptions: ToastDefaultOptions = {
  position: "neos-notification-top",
  timeout: 5000,
  type: "info",
};

export default class Toast {
  protected container?: HTMLElement;

  constructor() {
    this.container = document.getElementById("neos-notification-container");
    this._initialize();
  }

  _initialize() {
    if (isNil(this.container)) {
      const applicationContainer = document.getElementById("neos-application");
      if (!isNil(applicationContainer)) {
        this.container = document.createElement("div");
        this.container.id = "neos-notification-container";
        applicationContainer.appendChild(this.container);
      }
    }
  }

  /**
   * Internal function to creates a Message and adds them to the notification container
   *
   * @param {Object} options
   * @returns {void}
   */
  _create(options: MessageOptions) {
    const toastOptions: any = { ...defaultOptions, ...options };
    if (!isNil(toastOptions.position)) {
      this.container.classList.add(toastOptions.position);
    }

    new Message(toastOptions, this.container);
  }

  /**
   * Creates a new notification as Message
   *
   * @param {Object} options
   * @returns {void}
   */
  static create(options: MessageOptions) {
    // @ts-ignore
    if (isNil(this._container)) {
      const toast = new Toast();
      toast._create(options);
    } else {
      // @ts-ignore
      this._create(options);
    }
  }

  /**
   * Removes all messages within the notification container
   *
   * @returns {void}
   */
  static removeAll() {
    // @ts-ignore
    const messages: Array<HTMLElement> = Array.from(this._container.childNodes);
    messages.forEach((messageElement: HTMLElement) => {
      if (!isNil(messageElement)) {
        messageElement.classList.add("fade-out");
        setTimeout(() => {
          messageElement.remove();
        }, 250);
      }
    });
  }
}
