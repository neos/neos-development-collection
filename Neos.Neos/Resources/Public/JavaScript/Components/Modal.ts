import { isEmpty, isNil } from "../Helper";

export default class Modal {
  protected root?: HTMLElement;
  protected triggers: Array<HTMLElement>;
  protected closeButtons: Array<HTMLElement>;
  protected header?: HTMLElement;

  constructor(_root: HTMLElement) {
    this.root = _root;
    this.triggers = Array.from(
      document.querySelectorAll(`[href="#${_root.id}"][data-toggle="modal"]`)
    );
    this.closeButtons = Array.from(
      this.root.querySelectorAll('[data-dismiss="modal"]')
    );
    this.header = _root.querySelector(".neos-header");
    this._setupEventListeners();
  }

  _setupEventListeners() {
    this.triggers.forEach((_trigger) => {
      _trigger.addEventListener("click", this._open.bind(this));
    });

    this.closeButtons.forEach((_closeButton) => {
      _closeButton.addEventListener("click", this._close.bind(this));
    });

    document.addEventListener("keyup", this._onKeyPress.bind(this));
  }

  _open(_event: Event) {
    _event.preventDefault();
    const targetElement = <HTMLElement> _event.target;
    const trigger = this._getTriggerElement(targetElement);

    this._handleDynamicHeader(trigger);
    this.root.classList.add("open");
    this.root.classList.remove("neos-hide");

    trigger.dispatchEvent(
      new CustomEvent("neoscms-modal-opened", {
        bubbles: true,
        detail: { identifier: this.root.id },
      })
    );
  }

  /**
   * Trigger buttons can contain icons or text and so the event target is maybe not the mapping
   * button element. So this function checks for the data-toggle attribute and if this does not
   * exists we try to find the closest matching element.
   *
   * @param {object} _element
   * @return {object}
   */
  _getTriggerElement(_element: HTMLElement) {
    if (isNil(_element)) {
      return null;
    }

    if (!_element.hasAttribute("data-toggle")) {
      _element = _element.closest('[data-toggle="modal"]');
    }
    return _element;
  }

  _close() {
    this.root.classList.remove("open");
    this.root.classList.add("neos-hide");

    window.dispatchEvent(
      new CustomEvent("neoscms-modal-closed", {
        detail: { identifier: this.root.id },
      })
    );
  }

  _onKeyPress(_event: KeyboardEvent) {
    if (_event.key === "Escape") {
      this._close();
    }
  }

  _handleDynamicHeader(_trigger: HTMLElement) {
    if (isNil(_trigger) || isNil(this.header)) {
      return false;
    }

    const dynamicHeader = _trigger.getAttribute("data-modal-header");
    if (!isEmpty(dynamicHeader)) {
      this.header.innerText = dynamicHeader;
    }
  }
}
