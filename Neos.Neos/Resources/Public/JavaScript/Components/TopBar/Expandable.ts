export default class Expandable {
  protected root?: HTMLElement;
  protected trigger?: Array<HTMLElement>;
  protected onStateChange?: Function;

  constructor(
    _root: HTMLElement,
    _triggerClassName: string,
    _onStateChange: Function,
    initialState: Boolean
  ) {
    this.root = _root;
    this.trigger = Array.from(this.root.querySelectorAll(_triggerClassName));
    this.onStateChange = _onStateChange;
    this._setupEventListeners();
    this._initialize(initialState);
  }

  _setupEventListeners() {
    this.trigger.forEach((_toggleButton) => {
      _toggleButton.addEventListener("click", this._toggle.bind(this));
    });
  }

  _initialize(initialState: Boolean) {
    const header = this.root.querySelector("[aria-expanded]");
    header.setAttribute("aria-expanded", String(initialState));

    if (initialState) {
      // default is closed
      this.root.classList.add("neos-open");
      this._changeToogleIcon();
    }
  }

  _toggle() {
    this._changeToogleIcon();
    this.root.classList.toggle("neos-open");
    this._toogleAriaExpandable();
  }

  _toogleAriaExpandable() {
    const header = this.root.querySelector("[aria-expanded]");
    const expanded = this.root.classList.contains("neos-open");
    header.setAttribute("aria-expanded", String(expanded));
    if (typeof this.onStateChange === "function") {
      const sectionName = this.root.getAttribute("data-key");
      this.onStateChange(sectionName, expanded);
    }
  }

  _changeToogleIcon() {
    const openIcon = this.root.querySelector(".fa-chevron-circle-down");
    const closeIcon = this.root.querySelector(".fa-chevron-circle-up");
    if (openIcon) {
      openIcon.classList.replace(
        "fa-chevron-circle-down",
        "fa-chevron-circle-up"
      );
    }
    if (closeIcon) {
      closeIcon.classList.replace(
        "fa-chevron-circle-up",
        "fa-chevron-circle-down"
      );
    }
  }
}
