import { isEmpty, isNil } from "../Helper";

export default class DropDown {
  protected root?: HTMLElement;
  protected trigger: HTMLElement;
  protected content?: HTMLElement;
  protected grouped: Boolean;
  protected disabled: Boolean;

  constructor(
    _root: HTMLElement,
    _triggerElement: HTMLElement,
    _grouped: Boolean
  ) {
    const _trigger: HTMLElement = !isNil(_triggerElement)
      ? _triggerElement
      : _root.querySelector(".neos-dropdown-trigger");
    const contentSelector = this.getContentSelector(_trigger);

    this.root = _root;
    this.trigger = _trigger;
    this.content = !isEmpty(contentSelector)
      ? document.getElementById(contentSelector)
      : _root.querySelector(".neos-dropdown-content");
    this.grouped = isNil(_grouped) ? false : Boolean(_grouped);
    this.disabled = false;
    this.initialize();
    this.setupEventListeners();
  }

  /**
   * Disabled the drop down trigger if we have no content
   * or an empty content container.
   *
   * @returns {void}
   */
  private initialize(): void {
    if (isNil(this.content)) {
      return;
    }

    const innerContent = this.content.innerHTML.trim();
    if (isEmpty(innerContent)) {
      this.trigger.setAttribute("disabled", "true");
      this.disabled = true;
    }
  }

  /**
   * Returns an id that represents the content of the drop down.
   * If the trigger is not valid we just return an empty string.
   *
   * @param {HTMLElement} _element
   * @returns {string}
   */
  private getContentSelector(_element: HTMLElement): string {
    return !isNil(_element) ? _element.getAttribute("aria-controls") : "";
  }

  private setupEventListeners() {
    if (!isNil(this.trigger) && !isNil(this.content) && !this.disabled) {
      this.trigger.addEventListener("click", this.toggle.bind(this));
    }
  }

  /**
   * Toggle the drop down element. If the drop down is part of a drop down group
   * all other drop downs will be closed first.
   *
   * @param {Event} _event
   * @returns {void}
   */
  private toggle(_event: Event) {
    _event.preventDefault();
    if (this.grouped) {
      // close all other content panes when the dropdown is part of a button group
      this._closeOthers();
    } else {
      this.root.classList.toggle("open");
    }

    // get current state of the trigger
    const _triggerState = this.trigger.getAttribute("aria-expanded");
    const _state = !isEmpty(_triggerState)
      ? _triggerState.toLowerCase() === "true"
      : false;

    if (_state === false) {
      this.open();
    } else {
      this.close(this.trigger);
    }
  }

  /**
   * Opens drop down.
   *
   * @returns {void}
   */
  private open(): void {
    this.trigger.setAttribute("aria-expanded", 'true');
    this.content.removeAttribute("hidden");
  }

  /**
   * Closes the current drop down element or if the given trigger is not from
   * the current drop down, it closes the associated drop down.
   *
   * @param {HTMLElement} _trigger
   * @returns {void}
   */
  private close(_trigger: HTMLElement): void {
    const _contentSelector = this.getContentSelector(_trigger);
    let _content = document.getElementById(_contentSelector);
    if (isNil(_content)) {
      _content = this.content;
    }

    // close elements
    _trigger.setAttribute("aria-expanded", "false");
    _content.setAttribute("hidden", "true");
  }

  /**
   * Is fired when the drop down component is an grouped item.
   * So the drop down acts like an accordeon.
   *
   * @returns {void}
   */
  _closeOthers() {
    const dropDownElements = Array.from(
      this.root.querySelectorAll(".neos-dropdown-trigger")
    );
    dropDownElements.forEach((_element) => {
      if (_element !== this.trigger) {
        this.close(<HTMLElement>_element);
      }
    });
  }
}
