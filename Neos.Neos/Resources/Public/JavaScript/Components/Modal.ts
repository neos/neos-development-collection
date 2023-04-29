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
    this.setupEventListeners();
  }

  private setupEventListeners(): void {
    this.triggers.forEach((_trigger) => {
      _trigger.addEventListener("click", this.open.bind(this));
    });

    this.closeButtons.forEach((_closeButton) => {
      _closeButton.addEventListener("click", this.close.bind(this));
    });

    document.addEventListener("keyup", this.onKeyPress.bind(this));
  }

  private open(_event: Event): void {
    _event.preventDefault();
    const targetElement = <HTMLElement>_event.target;
    const trigger = this.getTriggerElement(targetElement);

    this.handleDynamicHeader(trigger);
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
   * @param {HTMLElement} _element
   * @return {HTMLElement}
   */
  private getTriggerElement(_element: HTMLElement): HTMLElement {
    if (isNil(_element)) {
      return null;
    }

    if (!_element.hasAttribute("data-toggle")) {
      _element = _element.closest('[data-toggle="modal"]');
    }
    return _element;
  }

  private close(): void {
    this.root.classList.remove("open");
    this.root.classList.add("neos-hide");

    window.dispatchEvent(
      new CustomEvent("neoscms-modal-closed", {
        detail: { identifier: this.root.id },
      })
    );
  }

  private onKeyPress(_event: KeyboardEvent): void {
    if (_event.key === "Escape") {
      this.close();
    }
  }

  private handleDynamicHeader(_trigger: HTMLElement): void {
    if (isNil(_trigger) || isNil(this.header)) {
      return;
    }

    const dynamicHeader = _trigger.getAttribute("data-modal-header");
    if (!isEmpty(dynamicHeader)) {
      this.header.innerText = dynamicHeader;
    }
  }
}
